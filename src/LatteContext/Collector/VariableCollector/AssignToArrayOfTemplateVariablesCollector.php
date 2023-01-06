<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\MixedType;

final class AssignToArrayOfTemplateVariablesCollector extends AbstractAssignVariableCollector
{
    private TemplateTypeResolver $templateTypeResolver;

    public function __construct(
        TemplateTypeResolver $templateTypeResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($lattePhpDocResolver);
        $this->templateTypeResolver = $templateTypeResolver;
    }

    public function collectVariables(Assign $node, Scope $scope): array
    {
        if (!($node->var instanceof Array_ || $node->var instanceof List_)) {
            return [];
        }

        /** @var ArrayItem[] $arrayItems */
        $arrayItems = ((array)$node->var->items);

        $types = [];
        $expressionTypes = $scope->getType($node->expr);
        if ($expressionTypes instanceof ConstantArrayType) {
            $types = $expressionTypes->getValueTypes();
        }

        $variables = [];
        foreach ($arrayItems as $key => $arrayItem) {
            $arrayItemValue = $arrayItem->value;
            if ($arrayItemValue instanceof Variable) {
                $var = $arrayItemValue;
                $nameNode = $arrayItemValue->name;
            } elseif ($arrayItemValue instanceof PropertyFetch) {
                $var = $arrayItemValue->var;
                $nameNode = $arrayItemValue->name;
            } else {
                continue;
            }

            if ($nameNode instanceof Expr) {
                continue;
            }

            $variableName = is_string($nameNode) ? $nameNode : $nameNode->name;

            $assignVariableType = $scope->getType($var);
            if (!$this->templateTypeResolver->resolve($assignVariableType)) {
                return [];
            }

            $variableType = $types[$key] ?? new MixedType();
            $variables[] = CollectedVariable::build($node, $scope, $variableName, $variableType);
        }
        return $variables;
    }
}
