<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorInterface;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Type\ObjectType;

final class FormDynamicInputsNodeVisitor extends NodeVisitorAbstract implements ExprTypeNodeVisitorInterface
{
    use ExprTypeNodeVisitorBehavior;

    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof Expression) {
            return null;
        }

        if (!$node->expr instanceof Assign) {
            return null;
        }

        $varName = $this->nameResolver->resolve($node->expr->var);
        if ($varName !== 'ʟ_input' && $varName !== '_input') {
            return null;
        }

        if ($node->expr->expr instanceof Ternary) {
            $node->expr->expr = $this->changeTernary($node->expr->expr);
        } elseif ($node->expr->expr instanceof Assign && $node->expr->expr->expr instanceof Ternary) {
            $node->expr->expr->expr = $this->changeTernary($node->expr->expr->expr);
        } else {
            return null;
        }

        return $node;
    }

    private function changeTernary(Ternary $ternary): Expr
    {
        if (!$ternary->cond instanceof FuncCall) {
            return $ternary;
        }

        $funcCall = $ternary->cond;
        if ($this->nameResolver->resolve($funcCall) !== 'is_object') {
            return $ternary;
        }

        /** @var Arg|null $firstArg */
        $firstArg = $funcCall->getArgs()[0] ?? null;
        if ($firstArg === null) {
            return $ternary;
        }

        $controlVariable = null;
        if ($firstArg->value instanceof Assign) {
            $controlVariable = $firstArg->value->expr;
        } elseif ($firstArg->value instanceof Variable) {
            $controlVariable = $firstArg->value;
        }

        if ($controlVariable === null) {
            return $ternary;
        }

        $type = $this->getType($controlVariable);
        if ($type === null) {
            return $ternary;
        }

        if ($type instanceof ObjectType) {
            return $controlVariable;
        }

        if ($type->isString()->yes() && $ternary->else instanceof ArrayDimFetch) {
            return new ArrayDimFetch($ternary->else->var, $controlVariable);
        }

        return $ternary;
    }
}
