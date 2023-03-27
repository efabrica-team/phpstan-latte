<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\VariablesNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\VariablesNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\TypeToPhpDoc;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable as VariableExpr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\NodeVisitorAbstract;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\NullType;

final class ChangeExtractArrayToVarTypesNodeVisitor extends NodeVisitorAbstract implements VariablesNodeVisitorInterface
{
    use VariablesNodeVisitorBehavior;

    /** @var array<string, string> */
    private array $globalVariables;

    private NameResolver $nameResolver;

    private TypeStringResolver $typeStringResolver;

    private TypeToPhpDoc $typeToPhpDoc;

    /**
     * @param array<string, string> $globalVariables
     */
    public function __construct(
        array $globalVariables,
        NameResolver $nameResolver,
        TypeStringResolver $typeStringResolver,
        TypeToPhpDoc $typeToPhpDoc
    ) {
        $this->globalVariables = $globalVariables;
        $this->nameResolver = $nameResolver;
        $this->typeStringResolver = $typeStringResolver;
        $this->typeToPhpDoc = $typeToPhpDoc;
    }

    public function beforeTraverse(array $nodes)
    {
        foreach ($this->globalVariables as $variable => $type) {
            $this->variables[$variable] = new Variable($variable, $this->typeStringResolver->resolve($type));
        }
        return null;
    }

    /**
     * @return Node[]|null
     */
    public function leaveNode(Node $node): ?array
    {
        if (!$node instanceof Expression) {
            return null;
        }

        $expr = $node->expr;
        if (!$expr instanceof FuncCall) {
            return null;
        }

        if ($this->nameResolver->resolve($expr) !== 'extract') {
            return null;
        }

        $firstArg = isset($expr->getArgs()[0]) ? $expr->getArgs()[0]->value : null;
        if (!$firstArg instanceof Array_) {
            return null;
        }

        $secondArg = isset($expr->getArgs()[1]) ? $expr->getArgs()[1]->value : null;

        $skipExisting = $this->nameResolver->resolve($secondArg) === 'EXTR_SKIP';

        $nodes = [];
        foreach ($firstArg->items as $item) {
            if ($item === null) {
                continue;
            }

            if (!$item->key instanceof String_) {
                continue;
            }

            $itemKey = $item->key->value;

            // if extract is used with EXTR_SKIP and we already have variable with this name, we should skip
            if ($skipExisting && isset($this->variables[$itemKey])) {
                continue;
            }

            $itemValue = $item->value;
            $itemValueType = null;

            if ($itemValue instanceof String_) {
                $itemValueType = new ConstantStringType($itemValue->value);
            } elseif ($itemValue instanceof DNumber) {
                $itemValueType = new ConstantFloatType($itemValue->value);
            } elseif ($itemValue instanceof LNumber) {
                $itemValueType = new ConstantIntegerType($itemValue->value);
            } elseif ($itemValue instanceof ConstFetch) {
                $constFetchName = $this->nameResolver->resolve($itemValue->name);
                if ($constFetchName === null) {
                    continue;
                }

                if (strtolower($constFetchName) === 'null') {
                    $itemValueType = new NullType();
                } elseif (in_array(strtolower($constFetchName), ['true', 'false'], true)) {
                    $itemValueType = new ConstantBooleanType(strtolower($constFetchName) === 'true');
                }
            }

            if ($itemValueType === null) {
                continue;
            }

            $prependVarTypesDocBlocks = sprintf(
                '/** @var %s $%s */',
                $this->typeToPhpDoc->toPhpDocString($itemValueType),
                $itemKey
            );

            $docNop = new Nop();
            $docNop->setDocComment(new Doc($prependVarTypesDocBlocks));
            $nodes[] = $docNop;
        }

        if ($nodes !== []) {
            $extractVariable = new VariableExpr('_extract_variable');
            $extractVariableAssign = new Assign($extractVariable, $firstArg);
            $nodes[] = new Expression($extractVariableAssign);
            $nodes[] = new Expression(new FuncCall(new Name('reset'), [new Arg($extractVariable)]));
        }
        return $nodes;
    }
}
