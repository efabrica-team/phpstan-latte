<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\List_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\MixedType;

final class AssignToArrayOfTemplateVariablesCollector extends AbstractAssignVariableCollector
{
    public function collectVariables(Assign $node, Scope $scope): array
    {
        if (!($node->var instanceof Array_ || $node->var instanceof List_)) {
            return [];
        }

        /** @var ArrayItem[] $arrayItems */
        $arrayItems = (array)$node->var->items;

        $types = [];
        $expressionTypes = $scope->getType($node->expr);
        if ($expressionTypes instanceof ConstantArrayType) {
            $types = $expressionTypes->getValueTypes();
        }

        $variables = [];
        foreach ($arrayItems as $key => $arrayItem) {
            $arrayItemValue = $arrayItem->value;
            $variableName = $this->getVariableName($arrayItemValue);
            if ($variableName === null) {
                continue;
            }

            if (!$this->isTemplateType($arrayItemValue, $scope)) {
                continue;
            }

            $variableType = $types[$key] ?? new MixedType();
            $variables[] = CollectedVariable::build($node, $scope, $variableName, $variableType);
        }
        return $variables;
    }
}
