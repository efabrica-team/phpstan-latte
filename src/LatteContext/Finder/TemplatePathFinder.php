<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplatePath;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;
use PHPStan\BetterReflection\BetterReflection;

final class TemplatePathFinder
{
    /**
     * @var array<string, array<string, array<?string>>>
     */
    private array $collectedTemplatePaths = [];

    private MethodCallFinder $methodCallFinder;

    private PathResolver $pathResolver;

    public function __construct(LatteContextData $latteContext, MethodCallFinder $methodCallFinder, PathResolver $pathResolver)
    {
        $this->methodCallFinder = $methodCallFinder;
        $this->pathResolver = $pathResolver;

        $collectedTemplatePaths = $latteContext->getCollectedData(CollectedTemplatePath::class);
        foreach ($collectedTemplatePaths as $collectedTemplatePath) {
            $className = $collectedTemplatePath->getClassName();
            $methodName = $collectedTemplatePath->getMethodName();
            if (!isset($this->collectedTemplatePaths[$className][$methodName])) {
                $this->collectedTemplatePaths[$className][$methodName] = [];
            }
            $templatePaths = $this->pathResolver->expand($collectedTemplatePath->getTemplatePath());
            if ($templatePaths !== null) {
                foreach ($templatePaths as $templatePath) {
                    $this->collectedTemplatePaths[$className][$methodName][] = $templatePath;
                }
            } else {
                $this->collectedTemplatePaths[$className][$methodName][] = null;
            }
        }
    }

    /**
     * @return array<?string>
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
     * @return array<?string>
     */
    private function findInParents(string $className)
    {
        $classReflection = (new BetterReflection())->reflector()->reflectClass($className);

        $collectedTemplatePaths = [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $collectedTemplatePaths = array_merge(
                $this->collectedTemplatePaths[$parentClass][''] ?? [],
                $collectedTemplatePaths
            );
        }
        return $collectedTemplatePaths;
    }

    /**
     * @return array<?string>
     */
    private function findInMethodCalls(string $className, string $methodName, string $currentClassName = null): array
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled) {
            return array_merge($this->collectedTemplatePaths[$declaringClass][$methodName] ?? [], ...$fromCalled);
        };
        return $this->methodCallFinder->traverseCalled($callback, $className, $methodName, $currentClassName);
    }
}
