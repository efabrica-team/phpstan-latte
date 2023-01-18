<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedFilter;
use Efabrica\PHPStanLatte\Template\Filter;
use PHPStan\Reflection\ReflectionProvider;

final class FilterFinder
{
    /**
     * @var array<string, array<string, Filter[]>>
     */
    private array $collectedFilters = [];

    private ReflectionProvider $reflectionProvider;

    private MethodCallFinder $methodCallFinder;

    public function __construct(LatteContextData $latteContext, ReflectionProvider $reflectionProvider, MethodCallFinder $methodCallFinder)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->methodCallFinder = $methodCallFinder;

        $collectedFilters = $latteContext->getCollectedData(CollectedFilter::class);
        foreach ($collectedFilters as $collectedFilter) {
            $className = $collectedFilter->getClassName();
            $methodName = $collectedFilter->getMethodName();
            if (!isset($this->collectedFilters[$className][$methodName])) {
                $this->collectedFilters[$className][$methodName] = [];
            }
            $this->collectedFilters[$className][$methodName][] = $collectedFilter->getFilter();
        }
    }

    /**
     * @return Filter[]
     */
    public function find(string $className, string ...$methodNames): array
    {
        $foundFilters = [
            $this->collectedFilters[$className][''] ?? [],
            $this->findInParents($className),
            $this->findInMethodCalls($className, '__construct'),
        ];
        foreach ($methodNames as $methodName) {
            $foundFilters[] = $this->findInMethodCalls($className, $methodName);
        }

        return array_merge(...$foundFilters);
    }

    /**
     * @return Filter[]
     */
    private function findInParents(string $className): array
    {
        $classReflection = $this->reflectionProvider->getClass($className);

        $collectedFilters = [];
        foreach ($classReflection->getParentClassesNames() as $parentClass) {
            $collectedFilters = array_merge(
                $this->collectedFilters[$parentClass][''] ?? [],
                $collectedFilters
            );
        }
        return $collectedFilters;
    }

    /**
     * @return Filter[]
     */
    private function findInMethodCalls(string $className, string $methodName, string $currentClassName = null): array
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled) {
            return array_merge($this->collectedFilters[$declaringClass][$methodName] ?? [], ...$fromCalled);
        };
        return $this->methodCallFinder->traverseCalled($callback, $className, $methodName, $currentClassName);
    }
}
