<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ComponentCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedComponent;
use Efabrica\PHPStanLatte\Template\Component;
use PHPStan\Node\CollectedDataNode;
use PHPStan\PhpDoc\TypeStringResolver;

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
     * @param array<CollectedComponent|array{className: string, methodName: string, component: array{name: string, type: string}}> $data
     * @return CollectedComponent[]
     */
    private function buildData(array $data): array
    {
        $collectedVariables = [];
        foreach ($data as $item) {
            if (!$item instanceof CollectedComponent) {
                $item['component'] = new Component($item['component']['name'], $this->typeStringResolver->resolve($item['component']['type']));
                $item = new CollectedComponent(...array_values($item));
            }
            $collectedVariables[] = $item;
        }
        return $collectedVariables;
    }
}
