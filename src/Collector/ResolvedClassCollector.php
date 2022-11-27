<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedClass;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @phpstan-import-type CollectedResolvedClassArray from CollectedResolvedClass
 * @implements Collector<Node, ?CollectedResolvedClassArray>
 */
final class ResolvedClassCollector implements Collector
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
     * @phpstan-return null|CollectedResolvedClassArray
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        foreach ($this->latteTemplateResolvers as $latteTemplateResolver) {
            if (!$latteTemplateResolver->check($node, $scope)) {
                continue;
            }
            return (new CollectedResolvedClass(get_class($latteTemplateResolver), $classReflection->getName()))->toArray();
        }
        return null;
    }
}
