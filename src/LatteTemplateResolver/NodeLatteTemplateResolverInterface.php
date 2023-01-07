<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

interface NodeLatteTemplateResolverInterface extends LatteTemplateResolverInterface
{
    /**
     * @return CollectedResolvedNode[]
     */
    public function collect(Node $node, Scope $scope): array;
}
