<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedNode;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @phpstan-import-type CollectedResolvedNodeArray from CollectedResolvedNode
 * @extends AbstractCollector<Node, CollectedResolvedNode, CollectedResolvedNodeArray>
 */
final class ResolvedNodeCollector extends AbstractCollector implements PHPStanLatteCollectorInterface
{
    /** @var LatteTemplateResolverInterface[] */
    private array $latteTemplateResolvers;

    /**
     * @param LatteTemplateResolverInterface[] $latteTemplateResolvers
     */
    public function __construct(TypeSerializer $typeSerializer, array $latteTemplateResolvers)
    {
        parent::__construct($typeSerializer);
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
                $resolvedNodes[] = $resolvedNode;
            }
        }
        return $this->collectItems($resolvedNodes);
    }
}
