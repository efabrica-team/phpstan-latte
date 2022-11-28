<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ResolvedNodeCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedNode;
use PHPStan\Node\CollectedDataNode;

/**
 * @phpstan-import-type CollectedResolvedNodeArray from CollectedResolvedNode
 */
final class ResolvedNodeFinder
{
    /**
     * @var array<string, CollectedResolvedNode[]>
     */
    private array $collectedResolvedNodes;

    public function __construct(CollectedDataNode $collectedDataNode)
    {
        $collectedResolvedNodes = $this->buildData(array_filter(array_merge(...array_values($collectedDataNode->get(ResolvedNodeCollector::class)))));
        foreach ($collectedResolvedNodes as $collectedResolvedNode) {
            $resolver = $collectedResolvedNode->getResolver();
            if (!isset($this->collectedResolvedNodes[$resolver])) {
                $this->collectedResolvedNodes[$resolver] = [];
            }
            $this->collectedResolvedNodes[$resolver][] = $collectedResolvedNode;
        }
    }

    /**
     * @return CollectedResolvedNode[]
     */
    public function find(string $resolver): array
    {
        return $this->collectedResolvedNodes[$resolver] ?? [];
    }

    /**
     * @phpstan-param array<CollectedResolvedNodeArray[]> $data
     * @return CollectedResolvedNode[]
     */
    private function buildData(array $data): array
    {
        $collectedResolvedNodes = [];
        foreach ($data as $itemList) {
            foreach ($itemList as $item) {
                $collectedResolvedNodes[] = CollectedResolvedNode::fromArray($item);
            }
        }
        return $collectedResolvedNodes;
    }
}
