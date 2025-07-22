<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\VariablesNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\VariablesNodeVisitorInterface;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable as VariableExpr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitorAbstract;
use PHPStan\PhpDoc\TypeStringResolver;

final class ChangeExtractArrayToVarTypesNodeVisitor extends NodeVisitorAbstract implements VariablesNodeVisitorInterface
{
    use VariablesNodeVisitorBehavior;

    /** @var array<string, string> */
    private array $globalVariables;

    private NameResolver $nameResolver;

    private TypeStringResolver $typeStringResolver;

    /**
     * @param array<string, string> $globalVariables
     */
    public function __construct(
        array $globalVariables,
        NameResolver $nameResolver,
        TypeStringResolver $typeStringResolver
    ) {
        $this->globalVariables = $globalVariables;
        $this->nameResolver = $nameResolver;
        $this->typeStringResolver = $typeStringResolver;
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

        $items = [];
        foreach ($firstArg->items as $item) {
            if (!$item->key instanceof String_) {
                continue;
            }

            $itemKey = $item->key->value;

            // if extract is used with EXTR_SKIP, and we already have variable with this name, we should skip
            if ($skipExisting && isset($this->variables[$itemKey])) {
                continue;
            }

            $items[] = $item;
        }

        if ($items === []) {
            return null;
        }

        $defaultVariables = new VariableExpr('__default_variables__');
        $defaultVariablesAssign = new Assign($defaultVariables, new Array_($items));

        $nodes = [];
        $nodes[] = new Expression($defaultVariablesAssign);
        $nodes[] = new Expression(new FuncCall(new Name('extract'), [new Arg($defaultVariables)]));
        return $nodes;
    }
}
