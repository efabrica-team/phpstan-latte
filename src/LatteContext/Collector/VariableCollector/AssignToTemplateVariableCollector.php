<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TypeResolver;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;

final class AssignToTemplateVariableCollector extends AbstractAssignVariableCollector
{
    private TypeResolver $typeResolver;

    private TemplateTypeResolver $templateTypeResolver;

    public function __construct(
        TypeResolver $typeResolver,
        TemplateTypeResolver $templateTypeResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($lattePhpDocResolver);
        $this->typeResolver = $typeResolver;
        $this->templateTypeResolver = $templateTypeResolver;
    }

    public function collectVariables(Assign $node, Scope $scope): array
    {
        if ($node->var instanceof Variable) {
            $var = $node->var;
            $nameNode = $node->var->name;
        } elseif ($node->var instanceof PropertyFetch) {
            $var = $node->var->var;
            $nameNode = $node->var->name;
        } else {
            return [];
        }

        if ($nameNode instanceof Expr) {
            $variableName = null;
        } else {
            $variableName = is_string($nameNode) ? $nameNode : $nameNode->name;
        }

        $assignVariableType = $scope->getType($var);
        if (!$this->templateTypeResolver->resolve($assignVariableType)) {
            return [];
        }

        $variableType = $this->typeResolver->resolveAsConstantType($node->expr, $scope);
        if ($variableType === null) {
            $variableType = $scope->getType($node->expr);
        }

        $variables = [];
        if ($variableName !== null) {
            $variables = [CollectedVariable::build($node, $scope, $variableName, $variableType)];
        }
        return $variables;
    }
}
