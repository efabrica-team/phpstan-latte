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
    private CalledClassResolver $calledClassResolver;

    private ReflectionProvider $reflectionProvider;

    private NameResolver $nameResolver;

    public function __construct(
        TypeSerializer $typeSerializer,
        CalledClassResolver $calledClassResolver,
        ReflectionProvider $reflectionProvider,
        NameResolver $nameResolver
    ) {
        parent::__construct($typeSerializer);
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
                    $relatedFiles[] = $parentClassReflection->getFileName();
                }
            }
        } elseif ($node instanceof New_) {
            $newClassName = $this->nameResolver->resolve($node->class);
            if  ($newClassName !== null) {
                $classReflection = $this->reflectionProvider->getClass($newClassName);
                if (!$classReflection->isInterface() && !$classReflection->isTrait()) {
                    $relatedFiles[] = $classReflection->getFileName();
                }
            }
        } elseif ($node instanceof CallLike) {
            $calledClassName = $this->calledClassResolver->resolve($node, $scope);
            if ($calledClassName !== null) {
                $classReflection = $this->reflectionProvider->getClass($calledClassName);
                if (!$classReflection->isInterface() && !$classReflection->isTrait()) {
                    $relatedFiles[] = $classReflection->getFileName();
                }
            }
        }
        return $this->collectItem(new CollectedRelatedFiles($scope->getFile(), $relatedFiles));
    }
}
