<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ComponentCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedComponent;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Node\CollectedDataNode;

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

    public function __construct(CollectedDataNode $collectedDataNode, TypeSerializer $typeSerializer, MethodCallFinder $methodCallFinder)
    {
        $this->methodCallFinder = $methodCallFinder;

        $componentsWithTypes = [];
        $collectedComponents = ComponentCollector::loadData($collectedDataNode, $typeSerializer, CollectedComponent::class);
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
}
