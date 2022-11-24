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
        /** @var CollectedResolvedClass[] $collectedResolvedClasses */
        $collectedResolvedClasses = array_merge(...array_values($collectedDataNode->get(ResolvedClassCollector::class)));
        foreach ($collectedResolvedClasses as $collectedResolvedClass) {
            $resolver = $collectedResolvedClass->getResolver();
            if (!isset($this->collectedResolvedClasses[$resolver])) {
                $this->collectedResolvedClasses[$resolver] = [];
            }
            $this->collectedResolvedClasses[$resolver][] = $collectedResolvedClass->getClassName();
        }
    }

    public function find(string $resolver): array
    {
        return $this->collectedResolvedClasses[$resolver] ?? [];
    }
}
