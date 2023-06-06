<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

final class RemoveExtractNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function leaveNode(Node $node): ?int
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

        // keep extract($__variables__);
        if (count($expr->getArgs()) === 1 && $expr->args[0]->value instanceof Variable && $this->nameResolver->resolve($expr->args[0]->value) === '__variables__') {
            return null;
        }

        return NodeTraverser::REMOVE_NODE;
    }
}
