<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

final class CleanupNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Expression) {
            // if only one expr in Expression is Variable, we can remove it
            if ($node->expr instanceof Variable) {
                return NodeTraverser::REMOVE_NODE;
            }
        }

        if ($node instanceof TryCatch) {
            // replace function call for closure with closure stmts
            $stmts = $node->stmts;
            if (count($stmts) === 1 && $stmts[0] instanceof Expression) {
                $expression = $stmts[0];
                if (!$expression->expr instanceof FuncCall) {
                    return null;
                }

                if (!$expression->expr->name instanceof Closure) {
                    return null;
                }

                if (count($expression->expr->getArgs()) !== 1) {
                    return null;
                }

                if ($this->nameResolver->resolve($expression->expr->getArgs()[0]->value) !== 'get_defined_vars') {
                    return null;
                }

                /** @var Closure $closure */
                $closure = $expression->expr->name;
                $node->stmts = $closure->stmts;
                return $node;
            }
        }

        return null;
    }
}
