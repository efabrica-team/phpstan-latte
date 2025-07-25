<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplatePath;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;
use PHPStan\Reflection\ReflectionProvider;

final class TemplatePathFinder
{
    /** @var array<string, array<string, array<string>>> */
    private array $collectedTemplatePaths = [];

    private ReflectionProvider $reflectionProvider;

    private MethodCallFinder $methodCallFinder;

    private MethodFinder $methodFinder;

    private PathResolver $pathResolver;

    public function __construct(LatteContextData $latteContext, ReflectionProvider $reflectionProvider, MethodCallFinder $methodCallFinder, MethodFinder $methodFinder, PathResolver $pathResolver)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->methodCallFinder = $methodCallFinder;
        $this->methodFinder = $methodFinder;
        $this->pathResolver = $pathResolver;

        $collectedTemplatePaths = $latteContext->getCollectedData(CollectedTemplatePath::class);
        foreach ($collectedTemplatePaths as $collectedTemplatePath) {
            $className = $collectedTemplatePath->getClassName();
            $methodName = $collectedTemplatePath->getMethodName();
            if (!isset($this->collectedTemplatePaths[$className][$methodName])) {
                $this->collectedTemplatePaths[$className][$methodName] = [];
            }
            $templatePaths = $this->pathResolver->expand($collectedTemplatePath->getTemplatePath(), $this->methodFinder);
            if ($templatePaths !== null) {
                foreach (array_filter($templatePaths) as $templatePath) {
                    $this->collectedTemplatePaths[$className][$methodName][] = $templatePath;
                }
            }
        }
    }

    /**
     * @param class-string $className
     * @return array<string>
     */
    public function find(string $className, string ...$methodNames): array
    {
        $foundTemplatePaths = [
            $this->collectedTemplatePaths[$className][''] ?? [],
            $this->findInParents($className),
            $this->findInMethodCalls($className, '__construct'),
        ];
        foreach ($methodNames as $methodName) {
            $foundTemplatePaths[] = $this->findInMethodCalls($className, $methodName);
        }
        return array_merge(...$foundTemplatePaths);
    }

    /**
     * @return array<string>
     */
    private function findInParents(string $className)
    {
        $classReflection = $this->reflectionProvider->getClass($className);

        $collectedTemplatePaths = [];
        foreach ($classReflection->getParentClassesNames() as $parentClass) {
            $collectedTemplatePaths = array_merge(
                $this->collectedTemplatePaths[$parentClass][''] ?? [],
                $collectedTemplatePaths
            );
        }
        return $collectedTemplatePaths;
    }

    /**
     * @param class-string $className
     * @param ?class-string $currentClassName
     * @return array<string>
     */
    private function findInMethodCalls(string $className, string $methodName, ?string $currentClassName = null): array
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled) {
            /** @var array<array<string>> $fromCalled */
            return array_merge($this->collectedTemplatePaths[$declaringClass][$methodName] ?? [], ...$fromCalled);
        };
        /** @var array<string> */
        return $this->methodCallFinder->traverseCalled($callback, $className, $methodName, $currentClassName);
    }
}
