<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorBehavior;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\NodeVisitorAbstract;

/**
 * changed output from:
 * <code>
 * foreach ($iterator = $ʟ_it = new LR\CachingIterator($stringList, $ʟ_it ?? null) as $string)
 * </code>
 *
 * to:
 * <code>
 * $iterator = $ʟ_it = new LR\CachingIterator($stringList, $ʟ_it ?? null)
 * foreach ($stringList as $string) {
 * </code>
 */
final class TransformForeachWithIteratorNodeVisitor extends NodeVisitorAbstract implements PostCompileNodeVisitorInterface
{
    use ActualClassNodeVisitorBehavior;

    /**
     * @return Node[]|null
     */
    public function leaveNode(Node $node): ?array
    {
        if (!$node instanceof Foreach_) {
            return null;
        }

        if (!$node->expr instanceof Assign) {
            return null;
        }

        if (!$node->expr->expr instanceof Assign) {
            return null;
        }

        if (!$node->expr->expr->expr instanceof New_) {
            return null;
        }

        /** @var New_ $construct */
        $construct = $node->expr->expr->expr;
        $constructArg = $construct->getArgs()[0] ?? null;
        if ($constructArg === null) {
            return null;
        }

        $nodes = [
            new Expression($node->expr),
        ];
        $node->expr = $constructArg->value;

        $nodes[] = $node;
        return $nodes;
    }
}
