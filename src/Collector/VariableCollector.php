<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedVariable;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Template\Variable as TemplateVariable;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

final class VariableCollector implements Collector
{
    private TemplateTypeResolver $templateTypeResolver;

    public function __construct(TemplateTypeResolver $templateTypeResolver)
    {
        $this->templateTypeResolver = $templateTypeResolver;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): ?CollectedVariable
    {
        // TODO add other variable assign resolvers - $template->setParameters(), $template->render(path, parameters) etc.

        if (!$node instanceof Assign) {
            return null;
        }

        if ($node->var instanceof Variable) {
            $var = $node->var;
            $nameNode = $node->var->name;
        } elseif ($node->var instanceof PropertyFetch) {
            $var = $node->var->var;
            $nameNode = $node->var->name;
        } else {
            return null;
        }

        if ($nameNode instanceof Expr) {
            return null;
        }

        $assignVariableType = $scope->getType($var);
        if (!$this->templateTypeResolver->resolve($assignVariableType)) {
            return null;
        }

        $variableName = is_string($nameNode) ? $nameNode : $nameNode->name;
        return new CollectedVariable(
            $scope->getClassReflection()->getName(),
            $scope->getFunctionName(),
            new TemplateVariable($variableName, $scope->getType($node->expr))
        );
    }
}