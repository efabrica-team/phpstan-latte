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
//        var_dump('class name');
//        var_dump($scope->getClassReflection() ? $scope->getClassReflection()->getName() : null);

        $relatedFiles = [];
        if ($node instanceof InClassNode) {
//            var_dump('in class node');
            $classReflection = $scope->getClassReflection();
            if ($classReflection !== null) {
//                var_dump('parents');
//                var_dump(array_map(function ($parent) {
//                    return $parent->getName();
//                }, $classReflection->getParents()));
                foreach ($classReflection->getParents() as $parentClassReflection) {
//                    var_dump('parent class file');
//                    var_dump($parentClassReflection->getFileName());
                    if ($this->isInCollectedPaths($parentClassReflection->getFileName())) {
                        $relatedFiles[] = $parentClassReflection->getFileName();
                    }
                }
            }
        } elseif ($node instanceof New_) {
            if ($node->class instanceof Expr) {
                $newClassNames = $this->valueResolver->resolveStrings($node->class, $scope) ?? [];
            } else {
                $newClassNames = [$this->nameResolver->resolve($node->class)];
            }

//            var_dump($newClassNames);

            foreach ($newClassNames as $newClassName) {
                if ($newClassName !== null && !in_array($newClassName, ['this', 'self', 'static', 'parent'], true)) {
                    $classReflection = $this->reflectionProvider->getClass($newClassName);
                    if (!$classReflection->isInterface() && !$classReflection->isTrait()) {
                        if ($this->isInCollectedPaths($classReflection->getFileName())) {
                            $relatedFiles[] = $classReflection->getFileName();
                        }
                    }
                }
            }
        } elseif ($node instanceof CallLike) {
            $calledClassName = $this->calledClassResolver->resolve($node, $scope);

//            var_dump($calledClassName);

            if ($calledClassName !== null && !in_array($calledClassName, ['this', 'self', 'static', 'parent'], true)) {
                $classReflection = $this->reflectionProvider->getClass($calledClassName);
                if (!$classReflection->isInterface() && !$classReflection->isTrait()) {
                    if ($this->isInCollectedPaths($classReflection->getFileName())) {
                        $relatedFiles[] = $classReflection->getFileName();
                    }
                }
            }
        }

        $relatedFiles = array_map('realpath', array_unique(array_filter($relatedFiles)));

//        print_R($relatedFiles);

        return [new CollectedRelatedFiles($scope->getFile(), $relatedFiles)];
    }

    private function isInCollectedPaths(?string $path): bool
    {
//        var_dump('Path: ' . $path);

        if ($path === null) {
            return false;
        }
        $path = realpath($path);
        if ($path === false) {
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
