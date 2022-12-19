<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedFilter;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TypeResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Efabrica\PHPStanLatte\Template\Filter;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends AbstractLatteContextCollector<MethodCall, CollectedFilter>
 */
final class FilterCollector extends AbstractLatteContextCollector
{
    private TypeResolver $typeResolver;

    private TemplateTypeResolver $templateTypeResolver;

    private ValueResolver $valueResolver;

    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        TypeResolver $typeResolver,
        TemplateTypeResolver $templateTypeResolver,
        ValueResolver $valueResolver
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->typeResolver = $typeResolver;
        $this->templateTypeResolver = $templateTypeResolver;
        $this->valueResolver = $valueResolver;
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @phpstan-return null|CollectedFilter[]
     */
    public function collectData(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $functionName = $scope->getFunctionName();
        if ($functionName === null) {
            return null;
        }

        $methodName = $this->nameResolver->resolve($node->name);
        if ($methodName !== 'addFilter') {
            return null;
        }

        $addFilterVariableType = $scope->getType($node->var);
        if (!$this->templateTypeResolver->resolve($addFilterVariableType)) {
            return null;
        }
        $args = $node->getArgs();
        if (count($args) < 2) {
            return null;
        }

        $filterNames = $this->valueResolver->resolve($args[0]->value, $scope);
        if ($filterNames === null || $filterNames === []) {
            return null;
        }

        $filterType = $this->typeResolver->resolveAsConstantType($args[1]->value, $scope);
        if ($filterType === null) {
            $filterType = $scope->getType($args[1]->value);
        }

        $collectedFilters = [];
        foreach ($filterNames as $filterName) {
            if (!is_string($filterName)) {
                continue;
            }
            $collectedFilters[] = new CollectedFilter(
                $classReflection->getName(),
                $functionName,
                new Filter($filterName, $filterType)
            );
        }
        return $collectedFilters;
    }
}
