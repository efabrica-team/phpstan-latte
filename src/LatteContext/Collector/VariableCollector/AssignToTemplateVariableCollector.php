<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TypeResolver;
use PhpParser\Node\Expr\Assign;
use PHPStan\Analyser\Scope;

final class AssignToTemplateVariableCollector extends AbstractAssignVariableCollector
{
    private TypeResolver $typeResolver;

    public function __construct(
        LattePhpDocResolver $lattePhpDocResolver,
        NameResolver $nameResolver,
        TypeResolver $typeResolver,
        TemplateTypeResolver $templateTypeResolver
    ) {
        parent::__construct($lattePhpDocResolver, $nameResolver, $templateTypeResolver);
        $this->typeResolver = $typeResolver;
    }

    public function collectVariables(Assign $node, Scope $scope): array
    {
        $variableName = $this->getVariableName($node->var);
        if ($variableName === null) {
            return [];
        }

        if (!$this->templateTypeResolver->resolveByNodeAndScope($node->var, $scope)) {
            return [];
        }

        $variableType = $this->typeResolver->resolveAsConstantType($node->expr, $scope);
        if ($variableType === null) {
            $variableType = $scope->getType($node->expr);
        }
        return [CollectedVariable::build($node, $scope, $variableName, $variableType)];
    }
}
