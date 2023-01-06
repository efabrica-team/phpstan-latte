<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedFilter;
use Efabrica\PHPStanLatte\Template\Filter;
use PHPStan\BetterReflection\BetterReflection;

final class FilterFinder
{
    /**
     * @var array<string, array<string, Filter[]>>
     */
    private array $collectedFilters = [];

    private MethodCallFinder $methodCallFinder;

    public function __construct(LatteContextData $latteContext, MethodCallFinder $methodCallFinder)
    {
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
    public function find(string $className, string $methodName): array
    {
        return array_merge(
            $this->collectedFilters[$className][''] ?? [],
            $this->findInParents($className),
            $this->findInMethodCalls($className, '__construct'),
            $this->findInMethodCalls($className, $methodName),
        );
    }

    /**
     * @return Filter[]
     */
    private function findInParents(string $className): array
    {
        $classReflection = (new BetterReflection())->reflector()->reflectClass($className);

        $collectedFilters = [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
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
