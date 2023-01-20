<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
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
        foreach ($nodes as $node) {
            if (!$node instanceof Class_) {
                continue;
            }
            $classStmts = $node->stmts;
            foreach ($classStmts as $classStmt) {
                if (!$classStmt instanceof ClassMethod) {
                    continue;
                }

                if (LatteVersion::isLatte2() && $this->nameResolver->resolve($classStmt) !== 'main' ||
                    LatteVersion::isLatte3() && $this->nameResolver->resolve($classStmt) !== 'prepare'
                ) {
                    continue;
                }

                $stmts = (array)$classStmt->stmts;
                foreach ($stmts as $stmt) {
                    if ($this->isEndOfTemplateHead($stmt)) {
                        break;
                    }
                    $this->definedVarsStatements[] = $stmt;
                }
            }
        }
        return null;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        if (LatteVersion::isLatte2() && $this->nameResolver->resolve($node) === 'main' ||
            $this->nameResolver->resolve($node) === 'prepare'
        ) {
            return null;
        }

        $statements = (array)$node->stmts;
        $node->stmts = array_merge($this->definedVarsStatements, $statements);
        return $node;
    }

    public function afterTraverse(array $nodes)
    {
        // reset defined vars
        $this->definedVarsStatements = [];
        return null;
    }

    private function isEndOfTemplateHead(Node $statement): bool
    {
        if ($statement instanceof Return_) {
            if ($statement->expr instanceof FuncCall) {
                // return get_defined_vars();
                if ($this->nameResolver->resolve($statement->expr) === 'get_defined_vars') {
                    return true;
                }
            }
        }
        if ($statement instanceof If_) {
            if ($statement->cond instanceof MethodCall) {
                // if ($this->getParentName()) { ... }
                if ($this->nameResolver->resolve($statement->cond) === 'getParentName') {
                    return true;
                }
            }
        }
        return false;
    }
}
