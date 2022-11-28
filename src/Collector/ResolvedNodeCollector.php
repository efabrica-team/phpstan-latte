<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedNode;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @phpstan-import-type CollectedResolvedNodeArray from CollectedResolvedNode
 * @implements Collector<Node, ?CollectedResolvedNodeArray[]>
 */
final class ResolvedNodeCollector implements Collector
{
    /** @var LatteTemplateResolverInterface[] */
    private array $latteTemplateResolvers;

    /**
     * @param LatteTemplateResolverInterface[] $latteTemplateResolvers
     */
    public function __construct(array $latteTemplateResolvers)
    {
        $this->latteTemplateResolvers = $latteTemplateResolvers;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @phpstan-return null|CollectedResolvedNodeArray[]
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $resolvedNodes = [];
        foreach ($this->latteTemplateResolvers as $latteTemplateResolver) {
            $resolvedNode = $latteTemplateResolver->collect($node, $scope);
            if ($resolvedNode !== null) {
                $resolvedNodes[] = $resolvedNode->toArray();
            }
        }
        return count($resolvedNodes) > 0 ? $resolvedNodes : null;
    }
}
