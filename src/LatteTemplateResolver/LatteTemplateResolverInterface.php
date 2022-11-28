<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedNode;
use Efabrica\PHPStanLatte\Template\Template;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;

interface LatteTemplateResolverInterface
{
    /** Try collect node in actual scope */
    public function collect(Node $node, Scope $scope): ?CollectedResolvedNode;

    /**
     * @return Template[]
     */
    public function findTemplates(CollectedResolvedNode $resolvedNode, CollectedDataNode $collectedDataNode): array;
}
