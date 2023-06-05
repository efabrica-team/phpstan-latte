<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorInterface;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

final class RemoveEmptyStringFromLabelAndControlPartNodeVisitor extends NodeVisitorAbstract implements ExprTypeNodeVisitorInterface
{
    use ExprTypeNodeVisitorBehavior;

    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof MethodCall) {
            return null;
        }

        if (!in_array($this->nameResolver->resolve($node), ['getControlPart', 'getLabelPart'], true)) {
            return null;
        }

        $callerType = $this->getType($node->var);
        if ($callerType === null) {
            return null;
        }

        $args = $node->getArgs();
        if (count($args) !== 1) {
            return null;
        }

        $argValue = $args[0]->value;
        if (!$argValue instanceof String_) {
            return null;
        }

        if ($argValue->value === '') {
            $node->args = [];
        }

        return $node;
    }
}
