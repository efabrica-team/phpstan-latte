<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\MethodCallCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedMethodCall;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Node\CollectedDataNode;

/**
 * @phpstan-import-type CollectedMethodCallArray from CollectedMethodCall
 */
final class MethodCallFinder
{
    /**
     * @var array<string, array<string, array<string, string[]>>>
     */
    private array $collectedMethodCalled = [];

    /**
     * @var array<string, array<string, array<string, string[]>>>
     */
    private array $collectedMethodCallers = [];

    /**
     * @var array<string, array<string, bool>>>
     */
    private array $hasTerminatingCalls = [];

    public function __construct(CollectedDataNode $collectedDataNode)
    {
        $collectedMethodCalls = MethodCallCollector::loadData($collectedDataNode, CollectedMethodCall::class);
        foreach ($collectedMethodCalls as $collectedMethodCall) {
            $callerClassName = $collectedMethodCall->getCallerClassName();
            $callerMethodName = $collectedMethodCall->getCallerMethodName();
            $calledClassName = $collectedMethodCall->getCalledClassName();
            $calledMethodName = $collectedMethodCall->getCalledMethodName();
            if ($collectedMethodCall->isCall()) {
                if (!isset($this->collectedMethodCalled[$callerClassName][$callerMethodName][$calledClassName])) {
                    $this->collectedMethodCalled[$callerClassName][$callerMethodName][$calledClassName] = [];
                }
                $this->collectedMethodCalled[$callerClassName][$callerMethodName][$calledClassName][] = $calledMethodName;
                if (!isset($this->collectedMethodCallers[$calledClassName][$calledMethodName][$callerClassName])) {
                    $this->collectedMethodCallers[$calledClassName][$calledMethodName][$callerClassName] = [];
                }
                $this->collectedMethodCallers[$calledClassName][$calledMethodName][$callerClassName][] = $callerMethodName;
            }
            if ($collectedMethodCall->isTerminatingCall()) {
                $this->hasTerminatingCalls[$callerClassName][$callerMethodName] = true;
            }
        }
    }

    /**
     * @return array<string, string[]>
     */
    public function findCalled(string $className, string $methodName): array
    {
        return $this->collectedMethodCalled[$className][$methodName] ?? [];
    }

    /**
     * @return array<string, string[]>
     */
    public function findCallers(string $className, string $methodName): array
    {
        return $this->collectedMethodCallers[$className][$methodName] ?? [];
    }

    public function hasTerminatingCalls(string $className, string $methodName): bool
    {
        return $this->hasTerminatingCalls[$className][$methodName] ?? false;
    }

    public function hasTerminatingCallsByMethod(ReflectionMethod $method): bool
    {
        return $this->hasTerminatingCalls($method->getDeclaringClass()->getName(), $method->getName());
    }
}
