<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;

final class AddCopyDefinedVarsMarkerNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    private bool $markerAdded = false;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function beforeTraverse(array $nodes)
    {
        $this->markerAdded = false;
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
        $newStatements = [];
        foreach ($statements as $statement) {
            if ($this->shouldBeMarkerAdded($statement)) {
                $newStatements[] = new Expression(new Assign(new Variable('___marker___'), new Variable('this')));
                $this->markerAdded = true;
            }
            $newStatements[] = $statement;
        }

        $node->stmts = $newStatements;
        return $node;
    }

    private function shouldBeMarkerAdded(Node $statement): bool
    {
        if ($this->markerAdded) {
            return false;
        }

        if ($statement instanceof Echo_) {
            // echo LR\Filters::escapeHtmlText($foo) or echo Nette\Bridges\FormsLatte\Runtime::renderFormBegin($foo) etc
            if (($statement->exprs[0] ?? null) instanceof StaticCall) {
                return true;
            }
        }
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
        if ($statement instanceof Expression) {
            if ($statement->expr instanceof FuncCall) {
                // \PhpStan\dumpType($foo)
                if (strtolower($this->nameResolver->resolve($statement->expr)) === 'phpstan\dumptype') {
                    return true;
                }
            }
            if ($statement->expr instanceof Assign) {
                if ($statement->expr->var instanceof Variable) {
                    // $_tmp = $this->global->uiControl->getComponent("foo");
                    if ($this->nameResolver->resolve($statement->expr->var->name) === '_tmp') {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
