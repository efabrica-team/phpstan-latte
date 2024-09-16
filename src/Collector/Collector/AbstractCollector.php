<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Collector;

use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedValueObject;
use PhpParser\Node;
use PHPStan\Collectors\Collector;
use PHPStan\Node\CollectedDataNode;

/**
 * @template N of Node
 * @template T of CollectedValueObject
 * @template A of array
 * @implements Collector<N, ?A[]>
 */
abstract class AbstractCollector implements Collector
{
    /**
     * @param class-string $class
     * @return T[]
     */
    public static function loadData(CollectedDataNode $collectedDataNode, string $class)
    {
        $data = array_filter(array_merge(...array_values($collectedDataNode->get(static::class))));
        $collected = [];
        foreach ($data as $itemList) {
            foreach ($itemList as $item) {
                $collected[] = $class::fromArray($item);
            }
        }
        return $collected;
    }

    /**
     * @phpstan-param array<T> $items
     * @return non-empty-array<A>|null
     */
    protected function collectItems(array $items): ?array
    {
        if (count($items) === 0) {
            return null;
        }
        $data = [];
        foreach ($items as $item) {
            $data[] = $item->toArray();
        }
        return $data;
    }
}
