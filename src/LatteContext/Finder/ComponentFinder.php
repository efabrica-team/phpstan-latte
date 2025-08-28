<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedComponent;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\ItemCombinator;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

final class ComponentFinder
{
    /** @var array<string, array<string, Component[]>> */
    private array $assignedComponents = [];

    /** @var array<string, array<string, Component[]>> */
    private array $declaredComponents = [];

    private ReflectionProvider $reflectionProvider;

    private MethodCallFinder $methodCallFinder;

    /** @var array<string, Component[]> */
    private $findCache = [];

    public function __construct(LatteContextData $latteContext, ReflectionProvider $reflectionProvider, MethodCallFinder $methodCallFinder)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->methodCallFinder = $methodCallFinder;

        $collectedComponents = $latteContext->getCollectedData(CollectedComponent::class);
        foreach ($collectedComponents as $collectedComponent) {
            $className = $collectedComponent->getClassName();
            $methodName = $collectedComponent->getMethodName();
            if (!isset($componentsWithTypes[$collectedComponent->getComponent()->getTypeAsString()])) {
                $componentsWithTypes[$collectedComponent->getComponent()->getTypeAsString()] = [];
            }
            $componentsWithTypes[$collectedComponent->getComponent()->getTypeAsString()][] = $collectedComponent->getComponent();

            if ($collectedComponent->isDeclared()) {
                $this->declaredComponents[$className][$methodName] = ItemCombinator::merge($this->declaredComponents[$className][$methodName] ?? [], [$collectedComponent->getComponent()]);
            } else {
                $this->assignedComponents[$className][$methodName] = ItemCombinator::union($this->assignedComponents[$className][$methodName] ?? [], [$collectedComponent->getComponent()]);
            }
        }

        /** @var array<string, Component[]> $componentsWithTypes */
        $componentsWithTypes = [];
        foreach (array_merge($this->declaredComponents, $this->assignedComponents) as $methods) {
            foreach ($methods as $components) {
                foreach ($components as $component) {
                    if (!isset($componentsWithTypes[$component->getTypeAsString()])) {
                        $componentsWithTypes[$component->getTypeAsString()] = [];
                    }
                    if ($component->getType() instanceof UnionType) {
                        foreach ($component->getType()->getTypes() as $type) {
                            $componentsWithTypes[$type->describe(VerbosityLevel::typeOnly())][] = $component;
                        }
                    } else {
                        $componentsWithTypes[$component->getType()->describe(VerbosityLevel::typeOnly())][] = $component;
                    }
                }
            }
        }

        foreach ($componentsWithTypes as $componentType => $components) {
            $subcomponents = array_merge(
                $this->assignedComponents[$componentType][''] ?? [],
                $this->assignedComponents[$componentType]['__construct'] ?? [],
                $this->declaredComponents[$componentType][''] ?? [],
                $this->declaredComponents[$componentType]['__construct'] ?? []
            );
            foreach ($components as $component) {
                $component->addSubcomponents($subcomponents);
            }
        }
    }

    /**
     * @param class-string $className
     * @return Component[]
     */
    public function find(string $className, string ...$methodNames): array
    {
        $cacheKey = $className . ' ' . implode(' ', $methodNames);
        if (!isset($this->findCache[$cacheKey])) {
            $foundComponents = [
                $this->findInClasses($className),
                $this->findInMethodCalls($className, '__construct'),
            ];
            foreach ($methodNames as $methodName) {
                $foundComponents[] = $this->findInMethodCalls($className, $methodName);
            }
            $this->findCache[$cacheKey] = ItemCombinator::merge(...$foundComponents);
        }
        return $this->findCache[$cacheKey];
    }

    /**
     * @return Component[]
     */
    private function findInClasses(string $className): array
    {
        $classReflection = $this->reflectionProvider->getClass($className);

        $assignedComponents = $this->assignedComponents[$className][''] ?? [];
        foreach ($classReflection->getParentClassesNames() as $parentClass) {
            $assignedComponents = ItemCombinator::union($this->assignedComponents[$parentClass][''] ?? [], $assignedComponents);
        }
        $declaredComponents = $this->declaredComponents[$className][''] ?? [];
        foreach ($classReflection->getParentClassesNames() as $parentClass) {
            $declaredComponents = ItemCombinator::merge($this->declaredComponents[$parentClass][''] ?? [], $declaredComponents);
        }
        return ItemCombinator::merge($assignedComponents, $declaredComponents);
    }

    /**
     * @param class-string $className
     * @param ?class-string $currentClassName
     * @return Component[]
     */
    private function findInMethodCalls(string $className, string $methodName, ?string $currentClassName = null): array
    {

        $callback = function (string $declaringClass, string $methodName, array $fromCalled, ?string $currentClassName) {
            /** @var array<Component[]> $fromCalled */
            /** @var Component[] $collectedComponents */
            $collectedComponents = ItemCombinator::resolveTemplateTypes(
                $this->assignedComponents[$declaringClass][$methodName] ?? [],
                $declaringClass,
                $currentClassName
            );
            $collectedComponents = ItemCombinator::union($collectedComponents, ...$fromCalled);
            $collectedComponents = ItemCombinator::merge($collectedComponents, $this->declaredComponents[$declaringClass][$methodName] ?? []);
            return $collectedComponents;
        };
        return $this->methodCallFinder->traverseCalled($callback, $className, $methodName, $currentClassName);
    }
}
