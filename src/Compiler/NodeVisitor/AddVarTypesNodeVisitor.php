<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\TypeToPhpDoc;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Nop;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Type\TypeCombinator;

final class AddVarTypesNodeVisitor extends NodeVisitorAbstract implements PostCompileNodeVisitorInterface
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

        $combinedVariables = [];
        foreach ($this->variables as $variable) {
            if (isset($combinedVariables[$variable->getName()])) {
                $combinedVariables[$variable->getName()] = new Variable(
                    $variable->getName(),
                    TypeCombinator::union($combinedVariables[$variable->getName()]->getType(), $variable->getType())
                );
            } else {
                $combinedVariables[$variable->getName()] = $variable;
            }
        }

        $statements = (array)$node->stmts;
        $variableStatements = [];
        foreach ($combinedVariables as $variable) {
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
