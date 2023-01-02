<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitorAbstract;

final class CopyDefinedVarsToOtherMethodsNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    /** @var Stmt[] */
    private array $definedVarsStatements = [];

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function beforeTraverse(array $nodes)
    {
        // reset defined vars
        $this->definedVarsStatements = [];
        return null;
    }

    public function enterNode(Node $node)
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        if (LatteVersion::isLatte2() && $this->nameResolver->resolve($node) === 'main' ||
            LatteVersion::isLatte3() && $this->nameResolver->resolve($node) === 'prepare'
        ) {
            $stmts = (array)$node->stmts;
            foreach ($stmts as $stmt) {
                $this->definedVarsStatements[] = $stmt;
                if ($this->isMarkerExpression($stmt)) {
                    break;
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node): ?Node
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        if (LatteVersion::isLatte2() && $this->nameResolver->resolve($node) === 'main' ||
            LatteVersion::isLatte3() && $this->nameResolver->resolve($node) === 'prepare'
        ) {
            return null;
        }

        $statements = (array)$node->stmts;
        $node->stmts = array_merge($this->definedVarsStatements, $statements);
        return $node;
    }

    private function isMarkerExpression(Node $stmt): bool
    {
        if (!$stmt instanceof Expression) {
            return false;
        }

        if (!$stmt->expr instanceof Assign) {
            return false;
        }

        if (!$stmt->expr->var instanceof Variable) {
            return false;
        }

        return $this->nameResolver->resolve($stmt->expr->var->name) === '___marker___';
    }
}
