<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TypeResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PHPStan\Analyser\Scope;

final class AssignToTemplateVariableCollector implements VariableCollectorInterface
{
    private NameResolver $nameResolver;

    private TemplateTypeResolver $templateTypeResolver;

    private TypeResolver $typeResolver;

    public function __construct(
        NameResolver $nameResolver,
        TemplateTypeResolver $templateTypeResolver,
        TypeResolver $typeResolver
    ) {
        $this->nameResolver = $nameResolver;
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
        if ($variableName === null) {
            return [];
        }

        $variableType = $this->typeResolver->resolveAsConstantType($node->expr, $scope);
        if ($variableType === null) {
            $variableType = $scope->getType($node->expr);
        }
        return [CollectedVariable::build($node, $scope, $variableName, $variableType)];
    }
}
