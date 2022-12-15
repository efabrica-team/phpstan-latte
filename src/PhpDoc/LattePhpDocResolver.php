<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\PhpDoc;

use Nette\Utils\Strings;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;

final class LattePhpDocResolver
{
    private PhpDocStringResolver $phpDocStringResolver;

    private ReflectionProvider $reflectionProvider;

    // TODO: Implement caching to prevent repeated parsing of same doc comment
    public function __construct(PhpDocStringResolver $phpDocStringResolver, ReflectionProvider $reflectionProvider)
    {
        $this->phpDocStringResolver = $phpDocStringResolver;
        $this->reflectionProvider = $reflectionProvider;
    }

    public function resolve(?string $commentText, ClassReflection $classReflection): LattePhpDoc
    {
        if ($commentText === null || $commentText === '') {
            return new LattePhpDoc();
        }
        $phpDocNode = $this->phpDocStringResolver->resolve($commentText);

        $isIgnored = count($phpDocNode->getTagsByName('@phpstan-latte-ignore')) > 0;

        $templateTags = $phpDocNode->getTagsByName('@phpstan-latte-template');
        if (count($templateTags) > 0) {
            $templatePaths = [];
            foreach ($templateTags as $templateTag) {
                if (!$templateTag->value instanceof GenericTagValueNode) {
                    continue;
                }
                $templatePaths[] = $this->replacePlaceholders($templateTag->value->value, $classReflection);
            }
        } else {
            $templatePaths = null;
        }

        return new LattePhpDoc($isIgnored, $templatePaths);
    }

    public function resolveForNode(Node $node, Scope $scope): LattePhpDoc
    {
        if ($scope->getClassReflection() === null) {
            return new LattePhpDoc();
        }
        $docNode = $node->getDocComment();
        if ($docNode === null) {
            return new LattePhpDoc();
        }
        $lattePhpDoc = $this->resolve($docNode->getText(), $scope->getClassReflection());
        if ($scope->getFunctionName() !== null) {
            $lattePhpDoc->setParent($this->resolveForMethod($scope->getClassReflection()->getName(), $scope->getFunctionName()));
        } else {
            $lattePhpDoc->setParent($this->resolveForClass($scope->getClassReflection()->getName()));
        }
        return $lattePhpDoc;
    }

    public function resolveForMethod(string $className, string $methodName): LattePhpDoc
    {
        $classReflection = $this->reflectionProvider->getClass($className);
        try {
            $commentText = $classReflection->getNativeMethod($methodName)->getDocComment();
            $lattePhpDoc = $this->resolve($commentText, $classReflection);
        } catch (MissingMethodFromReflectionException $e) {
            $lattePhpDoc = new LattePhpDoc(); // probably virtual method added by @method annotation
        }
        $lattePhpDoc->setParent($this->resolveForClass($className));
        return $lattePhpDoc;
    }

    public function resolveForClass(string $className): LattePhpDoc
    {
        $classReflection = $this->reflectionProvider->getClass($className);
        $commentText = $classReflection->getNativeReflection()->getDocComment() ?: null;
        return $this->resolve($commentText, $classReflection);
    }

    private function replacePlaceholders(string $value, ClassReflection $classReflection): string
    {
        $value = str_replace('{file}', $classReflection->getFileName() ?? '{file}', $value);
        $value = str_replace('{dir}', dirname($classReflection->getFileName() ?? '{dir}'), $value);
        $value = str_replace('{baseName}', pathinfo($classReflection->getFileName() ?? '{baseName}', PATHINFO_BASENAME), $value);
        $value = str_replace('{fileName}', pathinfo($classReflection->getFileName() ?? '{fileName}', PATHINFO_FILENAME), $value);
        $value = str_replace('{className}', Strings::after($classReflection->getName(), '\\', -1) ?? '{className}', $value);
        return $value;
    }
}
