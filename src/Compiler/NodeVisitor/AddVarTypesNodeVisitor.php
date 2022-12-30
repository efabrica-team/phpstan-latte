<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\TypeToPhpDoc;
use Efabrica\PHPStanLatte\Helper\VariablesHelper;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable as VariableExpr;
use PhpParser\Node\Stmt\ClassMethod;
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

        if (
            // TODO use name resolver
            LatteVersion::isLatte2() && $node->name->name !== 'main' ||
            LatteVersion::isLatte3() && $node->name->name !== 'prepare'
        ) {
            return null;
        }

        $combinedVariables = VariablesHelper::union($this->variables);

        $methodParams = [];
        foreach ($node->params as $param) {
            if ($param->var instanceof VariableExpr && is_string($param->var->name)) {
                $methodParams[] = $param->var->name;
            }
        }

        $statements = (array)$node->stmts;
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
        $node->stmts = array_merge($variableStatements, $statements);
        return $node;
    }
}
