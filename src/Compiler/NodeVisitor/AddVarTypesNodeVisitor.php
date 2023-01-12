<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\TypeToPhpDoc;
use Efabrica\PHPStanLatte\Template\ItemCombinator;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable as VariableExpr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\NodeVisitorAbstract;

final class AddVarTypesNodeVisitor extends NodeVisitorAbstract implements ActualClassNodeVisitorInterface
{
    use ActualClassNodeVisitorBehavior;

    /** @var Variable[] */
    private array $variables;

    private TypeToPhpDoc $typeToPhpDoc;

    /**
     * @param Variable[] $variables
     */
    public function __construct(array $variables, TypeToPhpDoc $typeToPhpDoc)
    {
        $this->variables = $variables;
        $this->typeToPhpDoc = $typeToPhpDoc;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        $combinedVariables = ItemCombinator::union($this->variables);

        $methodParams = [];
        foreach ($node->params as $param) {
            if ($param->var instanceof VariableExpr && is_string($param->var->name)) {
                $methodParams[] = $param->var->name;
            }
        }

        $variableStatements = [];
        foreach ($combinedVariables as $variable) {
            if (in_array($variable->getName(), $methodParams, true)) {
                continue;
            }
            $prependVarTypesDocBlocks = sprintf(
                '/** @var %s $%s */',
                $this->typeToPhpDoc->toPhpDocString($variable->getType()),
                $variable->getName()
            );

            // doc types node
            $docNop = new Nop();
            $docNop->setDocComment(new Doc($prependVarTypesDocBlocks));

            $variableStatements[] = $docNop;
        }

        $variableStatements[] = new Expression(
            new FuncCall(
                new Name('reset'),
                [
                    new Arg(new VariableExpr('this->params')),
                ]
            )
        );

        $node->stmts = array_merge($variableStatements, (array)$node->stmts);
        return $node;
    }
}
