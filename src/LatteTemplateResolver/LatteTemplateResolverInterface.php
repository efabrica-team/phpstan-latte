<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Template\Template;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;

interface LatteTemplateResolverInterface
{
    /** Checks if actual resolver can resolve node in actual scope */
    public function check(Node $node, Scope $scope): bool;

    /**
     * @return Template[]
     */
    public function findTemplates(string $className, CollectedDataNode $collectedDataNode): array;
}
