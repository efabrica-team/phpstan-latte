<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedRelatedFiles;
use Efabrica\PHPStanLatte\Resolver\CallResolver\CalledClassResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;

final class RelatedFilesCollector extends AbstractCollector implements PHPStanLatteCollectorInterface
{
    /** @var string[] */
    private array $collectedPaths;

    private CalledClassResolver $calledClassResolver;

    private ReflectionProvider $reflectionProvider;

    private NameResolver $nameResolver;

    /**
     * @param string[] $analysedPaths
     * @param string[] $collectedPaths
     */
    public function __construct(
        array $analysedPaths,
        array $collectedPaths,
        TypeSerializer $typeSerializer,
        CalledClassResolver $calledClassResolver,
        ReflectionProvider $reflectionProvider,
        NameResolver $nameResolver
    ) {
        parent::__construct($typeSerializer);
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
        $this->nameResolver = $nameResolver;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): ?array
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
            if  ($newClassName !== null) {
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
        return $this->collectItem(new CollectedRelatedFiles($scope->getFile(), array_unique($relatedFiles)));
    }

    private function isInCollectedPaths(string $path): bool
    {
        foreach ($this->collectedPaths as $collectedPath) {
            if (str_starts_with($path, $collectedPath)) {
                return true;
            }
        }
        return false;
    }
}
