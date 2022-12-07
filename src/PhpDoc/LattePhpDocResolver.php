<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;

final class LattePhpDocResolver
{
    private PhpDocStringResolver $phpDocStringResolver;

    private ReflectionProvider $reflectionProvider;

    public function __construct(PhpDocStringResolver $phpDocStringResolver, ReflectionProvider $reflectionProvider)
    {
        $this->phpDocStringResolver = $phpDocStringResolver;
        $this->reflectionProvider = $reflectionProvider;
    }

    public function resolve(?string $commentText): LattePhpDoc
    {
        if ($commentText === null || $commentText === '') {
            return new LattePhpDoc();
        }
        $phpDocNode = $this->phpDocStringResolver->resolve($commentText);
        $isIgnored = count($phpDocNode->getTagsByName('@phpstan-latte-ignore')) > 0;
        return new LattePhpDoc($isIgnored);
    }

    public function resolveForNode(Node $node, Scope $scope): LattePhpDoc
    {
        $docNode = $node->getDocComment();
        if ($docNode === null) {
            return new LattePhpDoc();
        }
        $lattePhpDoc = $this->resolve($docNode->getText());
        if ($scope->getClassReflection() !== null) {
            if ($scope->getFunctionName() !== null) {
                $lattePhpDoc = $lattePhpDoc->merge($this->resolveForMethod($scope->getClassReflection()->getName(), $scope->getFunctionName()));
            } else {
                $lattePhpDoc = $lattePhpDoc->merge($this->resolveForClass($scope->getClassReflection()->getName()));
            }
        }
        return $lattePhpDoc;
    }

    public function resolveForMethod(string $className, string $methodName): LattePhpDoc
    {
        try {
            $commentText = $this->reflectionProvider->getClass($className)->getNativeMethod($methodName)
                ->getDocComment();
        } catch (MissingMethodFromReflectionException $e) {
            return new LattePhpDoc(); // probably virtual method added by @method annotation
        }
        $lattePhpDoc = $this->resolve($commentText);
        $lattePhpDoc = $lattePhpDoc->merge($this->resolveForClass($className));
        return $lattePhpDoc;
    }

    public function resolveForClass(string $className): LattePhpDoc
    {
        $commentText = $this->reflectionProvider->getClass($className)->getNativeReflection()->getDocComment() ?: null;
        return $this->resolve($commentText);
    }
}
