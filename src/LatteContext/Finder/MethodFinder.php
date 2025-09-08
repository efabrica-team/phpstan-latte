<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedMethod;

final class MethodFinder
{
    /** @var array<string, array<string, CollectedMethod[]>> */
    private array $collectedMethods = [];

    private MethodCallFinder $methodCallFinder;

    /** @var array<string, CollectedMethod> */
    private $findCache = [];

    public function __construct(LatteContextData $latteContext, MethodCallFinder $methodCallFinder)
    {
        $this->methodCallFinder = $methodCallFinder;

        $collectedMethods = $latteContext->getCollectedData(CollectedMethod::class);
        foreach ($collectedMethods as $collectedMethod) {
            $className = $collectedMethod->getClassName();
            $methodName = $collectedMethod->getMethodName();
            if (!isset($this->collectedMethods[$className][$methodName])) {
                $this->collectedMethods[$className][$methodName] = [];
            }
            $this->collectedMethods[$className][$methodName][] = $collectedMethod;
        }
    }

    public function find(string $className, string $methodName): CollectedMethod
    {
        $cacheKey = $className . '::' . $methodName;
        if (!isset($this->findCache[$cacheKey])) {
            $className = $this->methodCallFinder->getDeclaringClass($className, $methodName) ?? $className;
            $this->findCache[$cacheKey] = CollectedMethod::combine($className, $methodName, ...$this->collectedMethods[$className][$methodName] ?? []);
        }
        return $this->findCache[$cacheKey];
    }

    /**
     * @param class-string $className
     */
    public function isAlwaysTerminated(string $className, string $methodName): bool
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled) {
            return array_merge([$this->find($declaringClass, $methodName)->isAlwaysTerminated()], ...$fromCalled);
        };
        $isAlwaysTerminated = $this->methodCallFinder->traverseAlwaysCalled($callback, $className, $methodName);
        return in_array(true, $isAlwaysTerminated, true);
    }

    /**
     * @param class-string $className
     */
    public function hasAnyAlwaysTerminated(string $className, string $methodName): bool
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled) {
            return array_merge([$this->find($declaringClass, $methodName)->isAlwaysTerminated()], ...$fromCalled);
        };
        $isAlwaysTerminated = $this->methodCallFinder->traverseCalled($callback, $className, $methodName);
        return in_array(true, $isAlwaysTerminated, true);
    }
}
