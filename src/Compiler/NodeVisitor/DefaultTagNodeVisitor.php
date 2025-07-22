<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp\Coalesce;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitorAbstract;

/**
 * Required to bypass limitation of PHPStan
 * See: https://github.com/phpstan/phpstan/issues/13273
 * Can be removed once this issue is resolved
 */
final class DefaultTagNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    public function __construct(
        NameResolver $nameResolver
    ) {
        $this->nameResolver = $nameResolver;
    }

    /**
     * @return Node[]|null
     */
    public function leaveNode(Node $node): ?array
    {
        if (!$node instanceof Expression) {
            return null;
        }

        if (!$node->expr instanceof Coalesce) {
            return null;
        }

        if (!$node->expr->expr instanceof Ternary) {
            return null;
        }

        if (!$node->expr->expr->cond instanceof FuncCall) {
            return null;
        }

        /** * @var FuncCall $funcCall */
        $funcCall = $node->expr->expr->cond;
        if ($this->nameResolver->resolve($funcCall) !== 'array_key_exists') {
            return null;
        }

        if (!$funcCall->args[0] instanceof Arg || !$funcCall->args[0]->value instanceof String_) {
            return null;
        }

        if (!$funcCall->args[1] instanceof Arg || !$funcCall->args[1]->value instanceof FuncCall) {
            return null;
        }

        /** * @var FuncCall $funcCall */
        $argFuncCall = $funcCall->args[1]->value;
        if ($this->nameResolver->resolve($argFuncCall) !== 'get_defined_vars') {
            return null;
        }

        $funcCall->args[1]->value = new Variable('__defined_vars__');

        return [
          new Expression(new Assign(new Variable('__defined_vars__'), $argFuncCall)),
          $node,
        ];
    }
}
