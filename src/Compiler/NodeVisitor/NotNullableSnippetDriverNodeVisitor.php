<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Type\ObjectType;

final class NotNullableSnippetDriverNodeVisitor extends NodeVisitorAbstract implements ExprTypeNodeVisitorInterface
{
    use ExprTypeNodeVisitorBehavior;

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof NullsafeMethodCall) {
            return null;
        }

        $callerType = $this->getType($node->var);
        if ($callerType !== null && ((new ObjectType('Nette\Bridges\ApplicationLatte\SnippetDriver'))->isSuperTypeOf($callerType)->yes() || (new ObjectType('Nette\Bridges\ApplicationLatte\SnippetRuntime'))->isSuperTypeOf($callerType)->yes())) {
            $methodCall = new MethodCall($node->var, $node->name, $node->args);
            $methodCall->setAttributes($node->getAttributes());
            return $methodCall;
        }

        return null;
    }
}
