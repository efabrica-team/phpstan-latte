<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Analyser;

use Efabrica\PHPStanLatte\LatteContext\Collector\AbstractLatteContextCollector;
use PhpParser\Node;

final class LatteContextCollectorRegistry
{
    /** @var array<class-string, AbstractLatteContextCollector[]> */
    private array $collectors = [];

    /** @var array<class-string, AbstractLatteContextCollector[]> */
    private array $cache = [];

    /**
     * @param AbstractLatteContextCollector[] $collectors
     */
    public function __construct(array $collectors)
    {
        foreach ($collectors as $collector) {
            foreach ($collector->getNodeTypes() as $nodeType) {
                $this->collectors[$nodeType][get_class($collector)] = $collector;
            }
        }
    }

    /**
     * @return AbstractLatteContextCollector[]
     */
    public function getCollectorsForNode(Node $node): array
    {
        $nodeType = get_class($node);
        if (!isset($this->cache[$nodeType])) {
            $parentNodeTypes = [$nodeType] + (array)class_parents($nodeType) + (array)class_implements($nodeType);
            $collectors = [];
            foreach ($parentNodeTypes as $parentNodeType) {
                foreach ($this->collectors[$parentNodeType] ?? [] as $collector) {
                    $collectors[get_class($collector)] = $collector;
                }
            }
            $this->cache[$nodeType] = $collectors;
        }

        return $this->cache[$nodeType];
    }
}
