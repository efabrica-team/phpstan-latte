<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedRelatedFiles;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;

final class RelatedFilesCollector extends AbstractCollector implements PHPStanLatteCollectorInterface
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $relatedFiles = [];
        foreach ($classReflection->getParents() as $parentClassReflection) {
            $relatedFiles[] = $parentClassReflection->getFileName();
        }

        return $this->collectItem(new CollectedRelatedFiles($scope->getFile(), $relatedFiles));
    }
}
