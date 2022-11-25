<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ResolvedClassCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedClass;
use PHPStan\Node\CollectedDataNode;

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
     * @param array<CollectedResolvedClass|array{resolver: string, className: string}> $data
     * @return CollectedResolvedClass[]
     */
    private function buildData(array $data): array
    {
        $collectedResolvedClasses = [];
        foreach ($data as $item) {
            if (!$item instanceof CollectedResolvedClass) {
                $item = new CollectedResolvedClass(...array_values($item));
            }
            $collectedResolvedClasses[] = $item;
        }
        return $collectedResolvedClasses;
    }
}
