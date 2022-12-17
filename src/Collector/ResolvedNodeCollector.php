<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedNode;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider;

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
        TypeSerializer $typeSerializer,
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        Parser $parser,
        NodeScopeResolver $nodeScopeResolver,
        array $latteTemplateResolvers,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($typeSerializer, $nameResolver, $reflectionProvider, $parser, $nodeScopeResolver);
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
    public function collectData(Node $node, Scope $scope): ?array
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
