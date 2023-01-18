<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\List_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\MixedType;

final class AssignToArrayOfTemplateVariablesCollector implements VariableCollectorInterface
{
    private NameResolver $nameResolver;

    protected TemplateTypeResolver $templateTypeResolver;

    public function __construct(
        NameResolver $nameResolver,
        TemplateTypeResolver $templateTypeResolver
    ) {
        $this->nameResolver = $nameResolver;
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

        $arrayItems = (array)$node->var->items;
        foreach ($arrayItems as $key => $arrayItem) {
            if ($arrayItem === null) {
                continue;
            }
            $arrayItemValue = $arrayItem->value;
            $variableName = $this->nameResolver->resolve($arrayItemValue);
            if ($variableName === null) {
                continue;
            }

            if (!$this->templateTypeResolver->resolveByNodeAndScope($arrayItemValue, $scope)) {
                continue;
            }

            $containsTemplateVariable = true;
            $variableType = $types[$key] ?? new MixedType();
            $variables[] = CollectedVariable::build($node, $scope, $variableName, $variableType);
        }

        return $containsTemplateVariable ? $variables : null;
    }
}
