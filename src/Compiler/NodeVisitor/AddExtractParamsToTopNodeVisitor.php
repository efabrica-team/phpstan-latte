<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
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
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        if (
            LatteVersion::isLatte2() && $this->nameResolver->resolve($node) !== 'main' ||
            LatteVersion::isLatte3() && $this->nameResolver->resolve($node) !== 'prepare'
        ) {
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
