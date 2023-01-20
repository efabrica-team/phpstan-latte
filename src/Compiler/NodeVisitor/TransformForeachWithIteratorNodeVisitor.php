<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
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
final class TransformForeachWithIteratorNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

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
        if (!$construct->class instanceof Name) {
            return null;
        }

        $name = $this->nameResolver->resolve($construct->class);
        if (!in_array($name, ['LR\CachingIterator', 'Latte\Runtime\CachingIterator', 'Latte\Essential\CachingIterator'], true)) {
            return null;
        }

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
