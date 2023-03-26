<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\TypeToPhpDoc;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Type\Constant\ConstantStringType;

final class RemoveExtractParamsNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    private TypeToPhpDoc $typeToPhpDoc;

    public function __construct(NameResolver $nameResolver, TypeToPhpDoc $typeToPhpDoc)
    {
        $this->nameResolver = $nameResolver;
        $this->typeToPhpDoc = $typeToPhpDoc;
    }

    /**
     * @return null|int|Node[]
     */
    public function leaveNode(Node $node)
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
        if ($firstArg === null) {
            return null;
        }

        if ($firstArg instanceof Variable) {
            if ($this->nameResolver->resolve($firstArg->name) === 'ʟ_args') {
                return NodeTraverser::REMOVE_NODE;
            }
        }

        if ($firstArg instanceof PropertyFetch) {
            if ($this->nameResolver->resolve($firstArg->var) === 'this' && $this->nameResolver->resolve($firstArg->name) === 'params') {
                return NodeTraverser::REMOVE_NODE;
            }
        }

        // TODO move to separate visitor
        if ($firstArg instanceof Array_) {
            $nodes = [];
            foreach ($firstArg->items as $item) {
                if ($item === null) {
                    continue;
                }

                if (!$item->key instanceof String_) {
                    continue;
                }

                $itemKey = $item->key->value;
                if ($itemKey === null) {
                    continue;
                }

                $itemValue = $item->value;
                $itemValueType = null;

                if ($itemValue instanceof String_) {
                    $itemValueType = new ConstantStringType($itemValue->value);
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

            $extractVariable = new Variable('_extract_variable');
            $extractVariableAssign = new Assign($extractVariable, $firstArg);
            $nodes[] = new Expression($extractVariableAssign);
            $nodes[] = new Expression(new FuncCall(new Name('reset'), [new Arg($extractVariable)]));
            return $nodes;
        }

        return null;
    }
}
