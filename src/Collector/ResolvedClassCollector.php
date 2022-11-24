<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedClass;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

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

    public function processNode(Node $node, Scope $scope): ?CollectedResolvedClass
    {
        foreach ($this->latteTemplateResolvers as $latteTemplateResolver) {
            if (!$latteTemplateResolver->check($node, $scope)) {
                continue;
            }
            return new CollectedResolvedClass(get_class($latteTemplateResolver), $scope->getClassReflection()->getName());
        }
        return null;
    }
}
