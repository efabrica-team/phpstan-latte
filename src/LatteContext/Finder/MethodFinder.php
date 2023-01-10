<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedMethod;

final class MethodFinder
{
    /**
     * @var array<string, array<string, CollectedMethod[]>>>
     */
    private array $collectedMethods;

    private MethodCallFinder $methodCallFinder;

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
        return CollectedMethod::combine($className, $methodName, ...$this->collectedMethods[$className][$methodName] ?? []);
    }

    public function hasAnyAlwaysTerminated(string $className, string $methodName): bool
    {
        return $this->findAnyAlwaysTerminatedInMethodCalls($className, $methodName);
    }

    private function findAnyAlwaysTerminatedInMethodCalls(string $className, string $methodName, string $currentClassName = null): bool
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled) {
            return array_merge([$this->find($declaringClass, $methodName)->isAlwaysTerminated()], ...$fromCalled);
        };
        $isAlwaysTerminated = $this->methodCallFinder->traverseCalled($callback, $className, $methodName, $currentClassName);
        return in_array(true, $isAlwaysTerminated, true);
    }
}
