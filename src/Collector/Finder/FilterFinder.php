<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\FilterCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedFilter;
use Efabrica\PHPStanLatte\Template\Filter;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Node\CollectedDataNode;

/**
 * @phpstan-import-type CollectedFilterArray from CollectedFilter
 */
final class FilterFinder
{
    /**
     * @var array<string, array<string, Filter[]>>
     */
    private array $collectedFilters = [];

    private MethodCallFinder $methodCallFinder;

    public function __construct(CollectedDataNode $collectedDataNode, TypeSerializer $typeSerializer, MethodCallFinder $methodCallFinder)
    {
        $this->methodCallFinder = $methodCallFinder;

        $collectedFilters = FilterCollector::loadData($collectedDataNode, $typeSerializer, CollectedFilter::class);
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
    public function findByMethod(ReflectionMethod $method): array
    {
        return $this->find($method->getDeclaringClass()->getName(), $method->getName());
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
     * @param array<string, array<string, true>> $alreadyFound
     * @return Filter[]
     */
    private function findInMethodCalls(string $className, string $methodName, array &$alreadyFound = []): array
    {
        if (isset($alreadyFound[$className][$methodName])) {
            return []; // stop recursion
        } else {
            $alreadyFound[$className][$methodName] = true;
        }

        $collectedFilters = [
            $this->collectedFilters[$className][$methodName] ?? [],
        ];

        $methodCalls = $this->methodCallFinder->findCalled($className, $methodName);
        foreach ($methodCalls as $calledClassName => $calledMethods) {
            foreach ($calledMethods as $calledMethod) {
                $collectedFilters[] = $this->findInMethodCalls($calledClassName, $calledMethod, $alreadyFound);
            }
        }

        return array_merge(...$collectedFilters);
    }
}
