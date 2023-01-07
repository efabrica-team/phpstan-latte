<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Collector;

use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;
use Efabrica\PHPStanLatte\LatteTemplateResolver\NodeLatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @phpstan-import-type CollectedResolvedNodeArray from CollectedResolvedNode
 * @extends AbstractCollector<Node, CollectedResolvedNode, CollectedResolvedNodeArray>
 */
final class ResolvedNodeCollector extends AbstractCollector
{
    /** @var NodeLatteTemplateResolverInterface[] */
    private array $latteTemplateResolvers;

    private LattePhpDocResolver $lattePhpDocResolver;

    /**
     * @param NodeLatteTemplateResolverInterface[] $latteTemplateResolvers
     */
    public function __construct(
        array $latteTemplateResolvers,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        $this->latteTemplateResolvers = $latteTemplateResolvers;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
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
            $resolvedNodes = array_merge($resolvedNodes, $latteTemplateResolver->collect($node, $scope));
        }
        if (count($resolvedNodes) > 0 && $this->lattePhpDocResolver->resolveForNode($node, $scope)->isIgnored()) {
            return null;
        }
        return $this->collectItems($resolvedNodes);
    }
}
