<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\LatteContext\Collector\AbstractLatteContextSubCollector;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TypeResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Analyser\Scope;

/**
 * @extends AbstractLatteContextSubCollector<CollectedVariable>
 */
final class AssignToTemplateVariableCollector extends AbstractLatteContextSubCollector implements VariableCollectorInterface
{
    private NameResolver $nameResolver;

    private ValueResolver $valueResolver;

    private TemplateTypeResolver $templateTypeResolver;

    private TypeResolver $typeResolver;

    public function __construct(
        NameResolver $nameResolver,
        ValueResolver $valueResolver,
        TemplateTypeResolver $templateTypeResolver,
        TypeResolver $typeResolver
    ) {
        $this->nameResolver = $nameResolver;
        $this->valueResolver = $valueResolver;
        $this->templateTypeResolver = $templateTypeResolver;
        $this->typeResolver = $typeResolver;
    }

    public function getNodeTypes(): array
    {
        return [Assign::class];
    }

    /**
     * @param Assign $node
     */
    public function collect(Node $node, Scope $scope): ?array
    {
        if (!$this->templateTypeResolver->resolveByNodeAndScope($node->var, $scope)) {
            return null;
        }

        $variableName = $this->nameResolver->resolve($node->var);
        if ($variableName !== null) {
            $variableNames = [$variableName];
        } elseif ($node->var instanceof PropertyFetch && $node->var->name instanceof Expr) {
            $variableNames = $this->valueResolver->resolveStrings($node->var->name, $scope);
        } else {
            return [];
        }

        if ($variableNames === null) {
            return [];
        }

        $variableType = $this->typeResolver->resolveAsConstantType($node->expr, $scope);
        if ($variableType === null) {
            $variableType = $scope->getType($node->expr);
        }

        $collectedVariables = [];
        foreach ($variableNames as $variableName) {
            $collectedVariables[] = CollectedVariable::build($node, $scope, $variableName, $variableType);
        }
        return $collectedVariables;
    }
}
