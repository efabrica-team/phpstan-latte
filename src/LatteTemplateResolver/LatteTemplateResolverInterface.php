<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedNode;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

interface LatteTemplateResolverInterface
{
    /** Try collect node in actual scope */
    public function collect(Node $node, Scope $scope): ?CollectedResolvedNode;

    public function resolve(CollectedResolvedNode $resolvedNode, LatteContextData $latteContext): LatteTemplateResolverResult;
}
