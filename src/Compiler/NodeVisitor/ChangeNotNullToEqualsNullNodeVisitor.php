<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;

/**
 * changed output from:
 * <code>
 * if (!$this->getReferringTemplate() || $this->getReferenceType() === "extends") {
 * </code>
 *
 * to:
 * <code>
 * if ($this->getReferringTemplate() === null || $this->getReferenceType() === "extends") {
 * </code>
 */
final class ChangeNotNullToEqualsNullNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof BooleanNot) {
            return null;
        }

        if (!$node->expr instanceof MethodCall) {
            return null;
        }

        if (!$node->expr->var instanceof Variable) {
            return null;
        }

        if ($node->expr->var->name !== 'this') {
            return null;
        }

        if ($this->nameResolver->resolve($node->expr->name) !== 'getReferringTemplate') {
            return null;
        }

        return new Identical($node->expr, new ConstFetch(new Name('null')));
    }
}
