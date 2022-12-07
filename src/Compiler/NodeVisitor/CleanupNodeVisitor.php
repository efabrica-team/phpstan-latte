<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable as NodeVariable;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

final class CleanupNodeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof Expression) {
            // if only one expr in Expression is Variable, we can remove it
            if ($node->expr instanceof NodeVariable) {
                return NodeTraverser::REMOVE_NODE;
            }
        }

        return null;
    }
}
