<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ComponentCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedComponent;
use Efabrica\PHPStanLatte\Template\Component;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Node\CollectedDataNode;
use PHPStan\PhpDoc\TypeStringResolver;

/**
 * @phpstan-import-type CollectedComponentArray from CollectedComponent
 */
final class ComponentFinder
{
    /**
     * @var array<string, array<string, Component[]>>
     */
    private array $collectedComponents = [];

    private MethodCallFinder $methodCallFinder;

    private TypeStringResolver $typeStringResolver;

    public function __construct(CollectedDataNode $collectedDataNode, MethodCallFinder $methodCallFinder, TypeStringResolver $typeStringResolver)
    {
        $this->methodCallFinder = $methodCallFinder;
        $this->typeStringResolver = $typeStringResolver;

        $componentsWithTypes = [];
        $collectedComponents = $this->buildData(array_filter(array_merge(...array_values($collectedDataNode->get(ComponentCollector::class)))));
        foreach ($collectedComponents as $collectedComponent) {
            $className = $collectedComponent->getClassName();
            $methodName = $collectedComponent->getMethodName();
            if (!isset($this->collectedComponents[$className][$methodName])) {
                $this->collectedComponents[$className][$methodName] = [];
            }
            if (!isset($componentsWithTypes[$collectedComponent->getComponent()->getTypeAsString()])) {
                $componentsWithTypes[$collectedComponent->getComponent()->getTypeAsString()] = [];
            }
            $componentsWithTypes[$collectedComponent->getComponent()->getTypeAsString()][] = $collectedComponent->getComponent();

            $this->collectedComponents[$className][$methodName][] = $collectedComponent->getComponent();
        }

        foreach ($componentsWithTypes as $componentType => $components) {
            $subcomponents = array_merge($this->collectedComponents[$componentType][''] ?? [], $this->collectedComponents[$componentType]['__construct'] ?? []);
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
        return array_merge(
            $this->collectedComponents[$className][''] ?? [],
            $this->findInParents($className),
            $this->findInMethodCalls($className, '__construct'),
            $this->findInMethodCalls($className, $methodName),
        );
    }

    /**
     * @return Component[]
     */
    public function findByMethod(ReflectionMethod $method): array
    {
        return $this->find($method->getDeclaringClass()->getName(), $method->getName());
    }

    /**
     * @return Component[]
     */
    private function findInParents(string $className)
    {
        $classReflection = (new BetterReflection())->reflector()->reflectClass($className);

        $collectedComponents = [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $collectedComponents = array_merge(
                $this->collectedComponents[$parentClass][''] ?? [],
                $collectedComponents
            );
        }
        return $collectedComponents;
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     * @return Component[]
     */
    private function findInMethodCalls(string $className, string $methodName, array &$alreadyFound = []): array
    {
        if (isset($alreadyFound[$className][$methodName])) {
            return []; // stop recursion
        } else {
            $alreadyFound[$className][$methodName] = true;
        }

        $collectedComponents = [
            $this->collectedComponents[$className][$methodName] ?? [],
        ];

        $methodCalls = $this->methodCallFinder->findCalled($className, $methodName);
        foreach ($methodCalls as $calledClassName => $calledMethods) {
            foreach ($calledMethods as $calledMethod) {
                $collectedComponents[] = $this->findInMethodCalls($calledClassName, $calledMethod, $alreadyFound);
            }
        }

        return array_merge(...$collectedComponents);
    }

    /**
     * @phpstan-param array<CollectedComponentArray[]> $data
     * @return CollectedComponent[]
     */
    private function buildData(array $data): array
    {
        $collectedComponents = [];
        foreach ($data as $itemList) {
            foreach ($itemList as $item) {
                $collectedComponents[] = CollectedComponent::fromArray($item, $this->typeStringResolver);
            }
        }
        return $collectedComponents;
    }
}
