<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitorAbstract;

final class AddExtractParamsToTopNodeVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof ClassMethod) {
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
