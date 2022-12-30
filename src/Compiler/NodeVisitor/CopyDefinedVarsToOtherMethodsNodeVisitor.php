<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

final class CopyDefinedVarsToOtherMethodsNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    private array $definedVarsStatements = [];

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function beforeTraverse(array $nodes)
    {
        // reset defined vars
        $this->definedVarsStatements = [];
    }

    public function enterNode(Node $node)
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        var_dump(count($this->definedVarsStatements));

        if (
            LatteVersion::isLatte2() && $this->nameResolver->resolve($node) === 'main' ||
            LatteVersion::isLatte3() && $this->nameResolver->resolve($node) === 'prepare'
        ) {
            $stmts = (array)$node->stmts;
            foreach ($stmts as $stmt) {
                if ($stmt instanceof Return_) {
                    continue;
                }
                if ($stmt instanceof If_) {
                    $ifStmts = $stmt->stmts;
                    $newIfStmts = [];
                    foreach ($ifStmts as $ifStmt) {
                        if ($ifStmt instanceof Return_) {
                            continue;
                        }
                        $newIfStmts[] = $ifStmt;
                    }
                    $stmt->stmts = $newIfStmts;
                }
                $this->definedVarsStatements[] = $stmt;
            }
        }

        return null;
    }

    public function leaveNode(Node $node): ?Node
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        if (
            LatteVersion::isLatte2() && $this->nameResolver->resolve($node) === 'main' ||
            LatteVersion::isLatte3() && $this->nameResolver->resolve($node) === 'prepare'
        ) {
            return null;
        }

        $statements = (array)$node->stmts;
        $node->stmts = array_merge($this->definedVarsStatements, $statements);
        return $node;
    }
}
