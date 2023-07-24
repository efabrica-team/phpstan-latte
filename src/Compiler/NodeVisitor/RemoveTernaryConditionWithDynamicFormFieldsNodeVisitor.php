<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorInterface;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Cast\String_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\NodeVisitorAbstract;

final class RemoveTernaryConditionWithDynamicFormFieldsNodeVisitor extends NodeVisitorAbstract implements ExprTypeNodeVisitorInterface
{
    use ExprTypeNodeVisitorBehavior;

    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof Ternary) {
            return null;
        }

        if (!$node->cond instanceof FuncCall) {
            return null;
        }

        if (!$node->else instanceof ArrayDimFetch) {
            return null;
        }
        if ($this->nameResolver->resolve($node->cond) !== 'is_object') {
            return null;
        }

        $functionArg = $node->cond->getArgs()[0] ?? null;
        if ($functionArg === null) {
            return null;
        }

        if (!$functionArg->value instanceof Assign) {
            return null;
        }

        $argValueType = $this->getType($functionArg->value->expr);
        if ($argValueType === null) {
            return null;
        }

        if ($argValueType->isObject()->yes()) {
            return $functionArg->value->expr;
        }

        $newDim = $functionArg->value->expr;
        if ($argValueType->isInteger()->yes()) {
            // cast integer to string, because now all names of form fields are strings
            $newDim = new String_($newDim);
        }

        $node->else->dim = $newDim;
        return $node->else;
    }
}
