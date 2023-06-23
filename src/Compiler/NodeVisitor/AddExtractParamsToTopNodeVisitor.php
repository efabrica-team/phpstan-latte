<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorInterface;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Type\ObjectType;

final class AddExtractParamsToTopNodeVisitor extends NodeVisitorAbstract implements ExprTypeNodeVisitorInterface
{
    use ExprTypeNodeVisitorBehavior;

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        $class = $node->getAttribute('parent');
        if (!$class instanceof Class_) {
            return null;
        }

        $type = $this->getType($class);
        if ($type === null || !(new ObjectType('\Latte\Runtime\Template'))->isSuperTypeOf($type)->yes()) {
            return null;
        }

        $statements = (array)$node->stmts;

        $extractParams = new Expression(
            new FuncCall(
                new Name('extract'),
                [
                    new Arg(new Variable('this->params')),
                ]
            )
        );

        $node->stmts = array_merge([$extractParams], $statements);
        return $node;
    }
}
