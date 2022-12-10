<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ResolvedNodeCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedNode;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PHPStan\Node\CollectedDataNode;

/**
 * @phpstan-import-type CollectedResolvedNodeArray from CollectedResolvedNode
 */
final class ResolvedNodeFinder
{
    /**
     * @var array<string, CollectedResolvedNode[]>
     */
    private array $collectedResolvedNodes = [];

    public function __construct(CollectedDataNode $collectedDataNode, TypeSerializer $typeSerializer)
    {
        $collectedResolvedNodes = ResolvedNodeCollector::loadData($collectedDataNode, $typeSerializer, CollectedResolvedNode::class);
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
}
