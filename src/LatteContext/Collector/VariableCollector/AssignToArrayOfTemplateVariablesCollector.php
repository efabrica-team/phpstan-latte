<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\LatteContext\Collector\AbstractLatteContextSubCollector;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\MixedType;

/**
 * @extends AbstractLatteContextSubCollector<CollectedVariable>
 */
final class AssignToArrayOfTemplateVariablesCollector extends AbstractLatteContextSubCollector implements VariableCollectorInterface
{
    private NameResolver $nameResolver;

    private ValueResolver $valueResolver;

    protected TemplateTypeResolver $templateTypeResolver;

    public function __construct(
        NameResolver $nameResolver,
        ValueResolver $valueResolver,
        TemplateTypeResolver $templateTypeResolver
    ) {
        $this->nameResolver = $nameResolver;
        $this->valueResolver = $valueResolver;
        $this->templateTypeResolver = $templateTypeResolver;
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
        if (!($node->var instanceof Array_ || $node->var instanceof List_)) {
            return null;
        }

        $types = [];
        $expressionTypes = $scope->getType($node->expr);
        if ($expressionTypes instanceof ConstantArrayType) {
            $types = $expressionTypes->getValueTypes();
        }

        $variables = [];
        $containsTemplateVariable = false;

        $arrayItems = $node->var->items;
        foreach ($arrayItems as $key => $arrayItem) {
            if ($arrayItem === null) {
                continue;
            }
            $arrayItemValue = $arrayItem->value;

            $resolvedVariableName = $this->nameResolver->resolve($arrayItemValue);
            if ($resolvedVariableName !== null) {
                $variableNames = [$resolvedVariableName];
            } elseif ($arrayItemValue instanceof PropertyFetch && $arrayItemValue->name instanceof Expr) {
                $variableNames = $this->valueResolver->resolveStrings($arrayItemValue->name, $scope);
            } else {
                continue;
            }

            if ($variableNames === null) {
                continue;
            }

            if (!$this->templateTypeResolver->resolveByNodeAndScope($arrayItemValue, $scope)) {
                continue;
            }

            $containsTemplateVariable = true;
            $variableType = $types[$key] ?? new MixedType();
            foreach ($variableNames as $variableName) {
                $variables[] = CollectedVariable::build($node, $scope, $variableName, $variableType);
            }
        }

        return $containsTemplateVariable ? $variables : null;
    }
}
