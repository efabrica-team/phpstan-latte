<?php

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedValueObject;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Collector;
use PHPStan\Node\CollectedDataNode;

/**
 * @template N of Node
 * @template T of CollectedValueObject
 * @template A of array
 * @implements Collector<N, ?A[]>
 */
abstract class AbstractCollector implements PHPStanLatteCollectorInterface
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
   * @param array<CollectedData> $collectedDataList
   * @param class-string $class
   * @return T[]
   */
    public function extractCollectedData(array $collectedDataList, string $class): array
    {
        $collectedTemplateRenders = [];
        foreach ($collectedDataList as $collectedData) {
            if ($collectedData->getCollectorType() !== static::class) {
                continue;
            }
            /** @phpstan-var A[] $dataList */
            $dataList = $collectedData->getData();
            foreach ($dataList as $data) {
                $collectedTemplateRenders[] = $class::fromArray($data);
            }
        }
        return $collectedTemplateRenders;
    }

    /**
     * @phpstan-param array<T> $items
     * @return ?A[]
     */
    public static function collectItems(array $items): ?array
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

    /**
     * @phpstan-param T $item
     * @return A[]
     */
    public static function collectItem(CollectedValueObject $item)
    {
        return [$item->toArray()];
    }

    /**
     * @phpstan-return null|A[]
     */
    abstract public function processNode(Node $node, Scope $scope): ?array;
}
