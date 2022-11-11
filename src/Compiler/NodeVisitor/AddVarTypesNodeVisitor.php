<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ScopedNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Template\Variable as TemplateVariable;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\NodeVisitorAbstract;

final class AddVarTypesNodeVisitor extends NodeVisitorAbstract implements PostCompileNodeVisitorInterface
{
    use ScopedNodeVisitorBehavior;

    /** @var TemplateVariable[] */
    private array $variables;

    /**
     * @param TemplateVariable[] $variables
     */
    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        $statements = (array)$node->stmts;
        $newStatements = [];
        foreach ($statements as $statement) {
            $newStatements[] = $statement;
            if (!$statement instanceof Expression) {
                continue;
            }

            if (!$statement->expr instanceof FuncCall) {
                continue;
            }

            if (!$statement->expr->name instanceof Name) {
                continue;
            }

            if ($statement->expr->name->toString() !== 'extract') {
                continue;
            }

            if (!isset($statement->expr->getArgs()[0])) {
                continue;
            }

            $argument = $statement->expr->getArgs()[0]->value;
            if (!$argument instanceof PropertyFetch) {
                continue;
            }

            if (!$argument->var instanceof Variable) {
                continue;
            }

            if ($argument->var->name !== 'this') {
                continue;
            }

            if (!$argument->name instanceof Identifier) {
                continue;
            }

            if ($argument->name->name !== 'params') {
                continue;
            }

            foreach ($this->variables as $variable) {
                $prependVarTypesDocBlocks = sprintf(
                    '/** @var %s $%s */',
                    $variable->getTypeAsString(),
                    $variable->getName()
                );

                // doc types node
                $docNop = new Nop();
                $docNop->setDocComment(new Doc($prependVarTypesDocBlocks));

                $newStatements[] = $docNop;
            }
        }
        $node->stmts = $newStatements;
        return $node;
    }
}
