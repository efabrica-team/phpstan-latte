<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ResolvedClassCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedClass;
use PHPStan\Node\CollectedDataNode;

/**
 * @phpstan-import-type CollectedResolvedClassArray from CollectedResolvedClass
 */
final class ResolvedClassFinder
{
    /**
     * @var array<string, string[]>
     */
    private array $collectedResolvedClasses;

    public function __construct(CollectedDataNode $collectedDataNode)
    {
        $collectedResolvedClasses = $this->buildData(array_filter(array_merge(...array_values($collectedDataNode->get(ResolvedClassCollector::class)))));
        foreach ($collectedResolvedClasses as $collectedResolvedClass) {
            $resolver = $collectedResolvedClass->getResolver();
            if (!isset($this->collectedResolvedClasses[$resolver])) {
                $this->collectedResolvedClasses[$resolver] = [];
            }
            $this->collectedResolvedClasses[$resolver][] = $collectedResolvedClass->getClassName();
        }
    }

    /**
     * @return string[]
     */
    public function find(string $resolver): array
    {
        return $this->collectedResolvedClasses[$resolver] ?? [];
    }

    /**
     * @phpstan-param array<CollectedResolvedClassArray> $data
     * @return CollectedResolvedClass[]
     */
    private function buildData(array $data): array
    {
        $collectedResolvedClasses = [];
        foreach ($data as $item) {
            $item = new CollectedResolvedClass($item['resolver'], $item['className']);
            $collectedResolvedClasses[] = $item;
        }
        return $collectedResolvedClasses;
    }
}
