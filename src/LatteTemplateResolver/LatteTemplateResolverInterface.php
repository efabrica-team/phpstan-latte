<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedNode;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;

interface LatteTemplateResolverInterface
{
    /** Try collect node in actual scope */
    public function collect(Node $node, Scope $scope): ?CollectedResolvedNode;

    /**
     * @return LatteTemplateResolverResult
     */
    public function resolve(CollectedResolvedNode $resolvedNode, CollectedDataNode $collectedDataNode): LatteTemplateResolverResult;
}
