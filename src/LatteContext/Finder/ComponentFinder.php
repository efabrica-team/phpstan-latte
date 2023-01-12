<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedComponent;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\ItemCombinator;
use PHPStan\BetterReflection\BetterReflection;

final class ComponentFinder
{
    /**
     * @var array<string, array<string, Component[]>>
     */
    private array $assignedComponents = [];

    /**
     * @var array<string, array<string, Component[]>>
     */
    private array $declaredComponents = [];

    private MethodCallFinder $methodCallFinder;

    public function __construct(LatteContextData $latteContext, MethodCallFinder $methodCallFinder)
    {
        $this->methodCallFinder = $methodCallFinder;

        /** @var array<string, Component[]> $componentsWithTypes */
        $componentsWithTypes = [];
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

        foreach ($componentsWithTypes as $componentType => $components) {
            $subcomponents = array_merge(
                $this->assignedComponents[$componentType][''] ?? [],
                $this->assignedComponents[$componentType]['__construct'] ?? [],
                $this->declaredComponents[$componentType][''] ?? [],
                $this->declaredComponents[$componentType]['__construct'] ?? []
            );
            foreach ($components as $component) {
                $component->setSubcomponents($subcomponents);
            }
        }
    }

    /**
     * @return Component[]
     */
    public function find(string $className, string ...$methodNames): array
    {
        $foundComponents = [
            $this->findInClasses($className),
            $this->findInMethodCalls($className, '__construct'),
        ];
        foreach ($methodNames as $methodName) {
            $foundComponents[] = $this->findInMethodCalls($className, $methodName);
        }
        return ItemCombinator::merge(...$foundComponents);
    }

    /**
     * @return Component[]
     */
    private function findInClasses(string $className): array
    {
        $classReflection = (new BetterReflection())->reflector()->reflectClass($className);

        $assignedComponents = $this->assignedComponents[$className][''] ?? [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $assignedComponents = ItemCombinator::union($this->assignedComponents[$parentClass][''] ?? [], $assignedComponents);
        }
        $declaredComponents = $this->declaredComponents[$className][''] ?? [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $declaredComponents = ItemCombinator::merge($this->declaredComponents[$parentClass][''] ?? [], $declaredComponents);
        }
        return ItemCombinator::merge($assignedComponents, $declaredComponents);
    }

    /**
     * @return Component[]
     */
    private function findInMethodCalls(string $className, string $methodName, string $currentClassName = null): array
    {

        $callback = function (string $declaringClass, string $methodName, array $fromCalled) {
            /** @var array<Component[]> $fromCalled */
            /** @var Component[] $collectedComponents */
            $collectedComponents = $this->assignedComponents[$declaringClass][$methodName] ?? [];
            $collectedComponents = ItemCombinator::union($collectedComponents, ...$fromCalled);
            $collectedComponents = ItemCombinator::merge($collectedComponents, $this->declaredComponents[$declaringClass][$methodName] ?? []);
            return $collectedComponents;
        };
        return $this->methodCallFinder->traverseCalled($callback, $className, $methodName, $currentClassName);
    }
}
