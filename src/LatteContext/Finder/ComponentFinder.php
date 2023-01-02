<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\Helper\ComponentsHelper;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedComponent;
use Efabrica\PHPStanLatte\Template\Component;
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
                $this->declaredComponents[$className][$methodName] = ComponentsHelper::merge($this->declaredComponents[$className][$methodName] ?? [], [$collectedComponent->getComponent()]);
            } else {
                $this->assignedComponents[$className][$methodName] = ComponentsHelper::union($this->assignedComponents[$className][$methodName] ?? [], [$collectedComponent->getComponent()]);
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
    public function find(string $className, string $methodName): array
    {
        return ComponentsHelper::merge(
            $this->findInClasses($className),
            $this->findInMethodCalls($className, '__construct'),
            $this->findInMethodCalls($className, $methodName),
        );
    }

    /**
     * @return Component[]
     */
    private function findInClasses(string $className): array
    {
        $classReflection = (new BetterReflection())->reflector()->reflectClass($className);

        $assignedComponents = $this->assignedComponents[$className][''] ?? [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $assignedComponents = ComponentsHelper::union($this->assignedComponents[$parentClass][''] ?? [], $assignedComponents);
        }
        $declaredComponents = $this->declaredComponents[$className][''] ?? [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $declaredComponents = ComponentsHelper::merge($this->declaredComponents[$parentClass][''] ?? [], $declaredComponents);
        }
        return ComponentsHelper::merge($assignedComponents, $declaredComponents);
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     * @return Component[]
     */
    private function findInMethodCalls(string $className, string $methodName, array &$alreadyFound = []): array
    {
        $declaringClass = $this->methodCallFinder->getDeclaringClass($className, $methodName);
        if (!$declaringClass) {
            return [];
        }

        if (isset($alreadyFound[$declaringClass][$methodName])) {
            return []; // stop recursion
        } else {
            $alreadyFound[$declaringClass][$methodName] = true;
        }

        $collectedComponents = $this->assignedComponents[$declaringClass][$methodName] ?? [];

        $methodCalls = $this->methodCallFinder->findCalled($className, $methodName);
        foreach ($methodCalls as $calledMethod) {
            $collectedComponents = ComponentsHelper::union($collectedComponents, $this->findInMethodCalls($calledMethod->getCalledClassName(), $calledMethod->getCalledMethodName(), $alreadyFound));
        }

        $collectedComponents = ComponentsHelper::merge($collectedComponents, $this->declaredComponents[$declaringClass][$methodName] ?? []);

        return $collectedComponents;
    }
}
