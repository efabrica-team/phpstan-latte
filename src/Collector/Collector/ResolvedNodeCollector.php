<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Collector;

use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @phpstan-import-type CollectedResolvedNodeArray from CollectedResolvedNode
 * @extends AbstractCollector<Node, CollectedResolvedNode, CollectedResolvedNodeArray>
 */
final class ResolvedNodeCollector extends AbstractCollector
{
    /** @var LatteTemplateResolverInterface[] */
    private array $latteTemplateResolvers;

    private LattePhpDocResolver $lattePhpDocResolver;

    /**
     * @param LatteTemplateResolverInterface[] $latteTemplateResolvers
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
        if ($this->lattePhpDocResolver->resolveForNode($node, $scope)->isIgnored()) {
            return null;
        }
        $resolvedNodes = [];
        foreach ($this->latteTemplateResolvers as $latteTemplateResolver) {
            $resolvedNode = $latteTemplateResolver->collect($node, $scope);
            if ($resolvedNode !== null) {
                $resolvedNodes[] = $resolvedNode;
            }
        }
        return $this->collectItems($resolvedNodes);
    }
}