<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ComponentCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedComponent;
use Efabrica\PHPStanLatte\Template\Component;
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

        $collectedComponents = $this->buildData(array_filter(array_merge(...array_values($collectedDataNode->get(ComponentCollector::class)))));
        foreach ($collectedComponents as $collectedComponent) {
            $className = $collectedComponent->getClassName();
            $methodName = $collectedComponent->getMethodName();
            if (!isset($this->collectedComponents[$className][$methodName])) {
                $this->collectedComponents[$className][$methodName] = [];
            }
            $this->collectedComponents[$className][$methodName][] = $collectedComponent->getComponent();
        }

        // TODO update subcomponents of components
    }

    /**
     * @return Component[]
     */
    public function find(string $className, string $methodName): array
    {
        $collectedComponents = [
            $this->collectedComponents[$className][$methodName] ?? [],
        ];

        // TODO check not only called method but also all parents

        $methodCalls = $this->methodCallFinder->find($className, $methodName);
        foreach ($methodCalls as $calledClassName => $calledMethods) {
            $collectedComponents[] = $this->find($calledClassName, '');
            foreach ($calledMethods as $calledMethod) {
                $collectedComponents[] = $this->find($calledClassName, $calledMethod);
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
        $collectedVariables = [];
        foreach ($data as $item) {
            $component = new Component($item['componentName'], $this->typeStringResolver->resolve($item['componentType']));
            $item = new CollectedComponent($item['className'], $item['methodName'], $component);
            $collectedVariables[] = $item;
        }
        return $collectedVariables;
    }
}
