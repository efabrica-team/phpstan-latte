<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ComponentCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedComponent;
use Efabrica\PHPStanLatte\Template\Component;
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
    private array $collectedComponents;

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
        return $this->findMethodCalls($className, $methodName);
    }

    /**
     * @return Component[]
     */
    public function findByMethod(ReflectionMethod $method): array
    {
        return $this->findMethodCalls($method->getDeclaringClass()->getName(), $method->getName());
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     * @return Component[]
     */
    private function findMethodCalls(string $className, string $methodName, array &$alreadyFound = []): array
    {
        if (isset($alreadyFound[$className][$methodName])) {
            return []; // stop recursion
        } else {
            $alreadyFound[$className][$methodName] = true;
        }

        // TODO check not only called method but also all parents

        $collectedComponents = [
            $this->collectedComponents[$className][$methodName] ?? [],
        ];

        $methodCalls = $this->methodCallFinder->findCalled($className, $methodName);
        foreach ($methodCalls as $calledClassName => $calledMethods) {
            $collectedComponents[] = $this->findMethodCalls($calledClassName, '', $alreadyFound);
            foreach ($calledMethods as $calledMethod) {
                $collectedComponents[] = $this->findMethodCalls($calledClassName, $calledMethod, $alreadyFound);
            }
        }

        return array_merge(...$collectedComponents);
    }

    /**
     * @phpstan-param array<CollectedComponentArray> $data
     * @return CollectedComponent[]
     */
    private function buildData(array $data): array
    {
        $collectedComponents = [];
        foreach ($data as $item) {
            $collectedComponents[] = CollectedComponent::fromArray($item, $this->typeStringResolver);
        }
        return $collectedComponents;
    }
}
