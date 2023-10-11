<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\If_;
use PhpParser\NodeVisitorAbstract;

/**
 * changed output from:
 * <code>
 * if ($this->getParentName()) {
 *     return \get_defined_vars();
 * }
 * </code>
 *
 * to:
 * <code>
 * if ($this->getParentName() !== null) {
 *     return \get_defined_vars();
 * }
 * </code>
 */
final class ChangeGetParentNameToCompareWithNullNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof If_) {
            return null;
        }

        if (!$node->cond instanceof MethodCall) {
            return null;
        }

        if (!$node->cond->var instanceof Variable) {
            return null;
        }

        if ($node->cond->var->name !== 'this') {
            return null;
        }

        if ($this->nameResolver->resolve($node->cond->name) !== 'getParentName') {
            return null;
        }

        $node->cond = new NotIdentical($node->cond, new ConstFetch(new Name('null')));
        return $node;
    }
}
