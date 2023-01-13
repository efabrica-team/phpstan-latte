<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedRelatedFiles;
use Efabrica\PHPStanLatte\Resolver\CallResolver\CalledClassResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends AbstractLatteContextCollector<Node, CollectedRelatedFiles>
 */
final class RelatedFilesCollector extends AbstractLatteContextCollector
{
    /** @var string[] */
    private array $collectedPaths;

    private ValueResolver $valueResolver;

    private CalledClassResolver $calledClassResolver;

    /**
     * @param string[] $analysedPaths
     * @param string[] $collectedPaths
     */
    public function __construct(
        array $analysedPaths,
        array $collectedPaths,
        NameResolver $nameResolver,
        ValueResolver $valueResolver,
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
        $this->valueResolver = $valueResolver;
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
                    $filename = $this->getFilename($parentClassReflection);
                    if ($this->isInCollectedPaths($filename)) {
                        $relatedFiles[] = $filename;
                    }
                }
            }
        } elseif ($node instanceof New_) {
            if ($node->class instanceof Expr) {
                $newClassNames = $this->valueResolver->resolveStrings($node->class, $scope) ?? [];
            } else {
                $newClassNames = [$this->nameResolver->resolve($node->class)];
            }
            foreach ($newClassNames as $newClassName) {
                if ($newClassName !== null && !in_array($newClassName, ['this', 'self', 'static', 'parent'], true)) {
                    $classReflection = $this->reflectionProvider->getClass($newClassName);
                    if (!$classReflection->isInterface() && !$classReflection->isTrait()) {
                        $filename = $this->getFilename($classReflection);
                        if ($this->isInCollectedPaths($filename)) {
                            $relatedFiles[] = $filename;
                        }
                    }
                }
            }
        } elseif ($node instanceof CallLike) {
            $calledClassName = $this->calledClassResolver->resolve($node, $scope);
            if ($calledClassName !== null && !in_array($calledClassName, ['this', 'self', 'static', 'parent'], true)) {
                $classReflection = $this->reflectionProvider->getClass($calledClassName);
                if (!$classReflection->isInterface() && !$classReflection->isTrait()) {
                    $filename = $this->getFilename($classReflection);
                    if ($this->isInCollectedPaths($filename)) {
                        $relatedFiles[] = $filename;
                    }
                }
            }
        }

        $relatedFiles = array_unique(array_filter($relatedFiles));
        return [new CollectedRelatedFiles($scope->getFile(), $relatedFiles)];
    }

    private function getFilename(ClassReflection $classReflection): ?string
    {
        $filename = $classReflection->getFileName();
        if ($filename === null) {
            return null;
        }
        $realpath = realpath($filename);
        return $realpath === false ? null : $realpath;
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
