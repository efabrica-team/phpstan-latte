<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ComponentCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedComponent;
use Efabrica\PHPStanLatte\Template\Component;
use PHPStan\Node\CollectedDataNode;

final class ComponentFinder
{
    /**
     * @var array<string, array<string, Component[]>
     */
    private array $collectedComponents;

    public function __construct(CollectedDataNode $collectedDataNode)
    {
        /** @var CollectedComponent[] $collectedComponents */
        $collectedComponents = array_merge(...array_values($collectedDataNode->get(ComponentCollector::class)));
        foreach ($collectedComponents as $collectedComponent) {
            $className = $collectedComponent->getClassName();
            $methodName = $collectedComponent->getMethodName();
            if (!isset($this->collectedComponents[$className][$methodName])) {
                $this->collectedComponents[$className][$methodName] = [];
            }
            $this->collectedComponents[$className][$methodName][] = $collectedComponent->getComponent();
        }
    }

    public function find(string $className, string $methodName): array
    {
        return $this->collectedComponents[$className][$methodName] ?? [];
    }
}
