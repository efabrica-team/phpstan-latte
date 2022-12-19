<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedRelatedFiles;
use Efabrica\PHPStanLatte\Resolver\CallResolver\CalledClassResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends AbstractLatteContextCollector<Node, CollectedRelatedFiles>
 */
final class RelatedFilesCollector extends AbstractLatteContextCollector
{
    /** @var string[] */
    private array $collectedPaths;

    private CalledClassResolver $calledClassResolver;

    /**
     * @param string[] $analysedPaths
     * @param string[] $collectedPaths
     */
    public function __construct(
        array $analysedPaths,
        array $collectedPaths,
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        CalledClassResolver $calledClassResolver
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->collectedPaths = $analysedPaths;
        foreach ($collectedPaths as $collectedPath) {
            $realPath = realpath($collectedPath);
            if ($realPath === false) {
                continue;
            }
            if (file_exists($realPath)) {
                $this->collectedPaths[] = $realPath;
            }
        }
        $this->calledClassResolver = $calledClassResolver;
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function collectData(Node $node, Scope $scope): ?array
    {
        $relatedFiles = [];
        if ($node instanceof InClassNode) {
            $classReflection = $scope->getClassReflection();
            if ($classReflection !== null) {
                foreach ($classReflection->getParents() as $parentClassReflection) {
                    if ($this->isInCollectedPaths($parentClassReflection->getFileName())) {
                        $relatedFiles[] = $parentClassReflection->getFileName();
                    }
                }
            }
        } elseif ($node instanceof New_) {
            $newClassName = $this->nameResolver->resolve($node->class);
            if ($newClassName !== null) {
                $classReflection = $this->reflectionProvider->getClass($newClassName);
                if (!$classReflection->isInterface() && !$classReflection->isTrait()) {
                    if ($this->isInCollectedPaths($classReflection->getFileName())) {
                        $relatedFiles[] = $classReflection->getFileName();
                    }
                }
            }
        } elseif ($node instanceof CallLike) {
            $calledClassName = $this->calledClassResolver->resolve($node, $scope);
            if ($calledClassName !== null) {
                $classReflection = $this->reflectionProvider->getClass($calledClassName);
                if (!$classReflection->isInterface() && !$classReflection->isTrait()) {
                    if ($this->isInCollectedPaths($classReflection->getFileName())) {
                        $relatedFiles[] = $classReflection->getFileName();
                    }
                }
            }
        }

        $relatedFiles = array_unique(array_filter($relatedFiles));
        return [new CollectedRelatedFiles($scope->getFile(), $relatedFiles)];
    }

    private function isInCollectedPaths(?string $path): bool
    {
        if ($path === null) {
            return false;
        }
        foreach ($this->collectedPaths as $collectedPath) {
            if (str_starts_with($path, $collectedPath)) {
                return true;
            }
        }
        return false;
    }
}
