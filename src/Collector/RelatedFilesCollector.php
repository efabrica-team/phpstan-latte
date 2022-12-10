<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;

final class RelatedFilesCollector implements PHPStanLatteCollectorInterface
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     */
    public function processNode(Node $node, Scope $scope)
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $relatedFiles = [];
        foreach ($classReflection->getParents() as $parentClassReflection) {
            $relatedFiles[] = $parentClassReflection->getFileName();
        }

        return array_unique(array_filter($relatedFiles));
    }
}
