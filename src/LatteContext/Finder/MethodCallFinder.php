<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedMethodCall;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;

final class MethodCallFinder
{
    /**
     * @var array<string, array<string, CollectedMethodCall[]>>
     */
    private array $collectedMethodCalled = [];

    /**
     * @var array<string, array<string, bool>>>
     */
    private array $hasTerminatingCalls = [];

    /**
     * @var array<string, array<string, bool>>>
     */
    private array $hasOutputCalls = [];

    public function __construct(LatteContextData $latteContext)
    {
        $collectedMethodCalls = $latteContext->getCollectedData(CollectedMethodCall::class);
        foreach ($collectedMethodCalls as $collectedMethodCall) {
            $callerClassName = $collectedMethodCall->getCallerClassName();
            $callerMethodName = $collectedMethodCall->getCallerMethodName();
            if ($collectedMethodCall->isTerminatingCall()) {
                $this->hasTerminatingCalls[$callerClassName][$callerMethodName] = true;
            } elseif ($collectedMethodCall->isOutputCall()) {
                $this->hasOutputCalls[$callerClassName][$callerMethodName] = true;
            } else {
                if (!isset($this->collectedMethodCalled[$callerClassName][$callerMethodName])) {
                    $this->collectedMethodCalled[$callerClassName][$callerMethodName] = [];
                }
                $this->collectedMethodCalled[$callerClassName][$callerMethodName][] = $collectedMethodCall;
            }
        }
    }

    /**
     * @return CollectedMethodCall[]
     */
    public function findCalled(string $className, string $methodName): array
    {
        return $this->collectedMethodCalled[$className][$methodName] ?? [];
    }

    /**
     * @return CollectedMethodCall[]
     */
    public function findCalledByMethod(ReflectionMethod $method): array
    {
        return $this->findCalled($method->getDeclaringClass()->getName(), $method->getName());
    }

    /**
     * @return CollectedMethodCall[]
     */
    public function findCalledOfType(string $className, string $methodName, string $type): array
    {
        $calledByType = [];
        foreach ($this->collectedMethodCalled[$className][$methodName] ?? [] as $called) {
            if ($called->getType() === $type) {
                $calledByType[] = $called;
            }
        }
        return $calledByType;
    }

    /**
     * @return CollectedMethodCall[]
     */
    public function findCalledOfTypeByMethod(ReflectionMethod $method, string $type): array
    {
        return $this->findCalledOfType($method->getDeclaringClass()->getName(), $method->getName(), $type);
    }

    public function hasAnyTerminatingCalls(string $className, string $methodName): bool
    {
        return $this->findAnyTerminatingCallsInMethodCalls($className, $methodName);
    }

    public function hasAnyTerminatingCallsByMethod(ReflectionMethod $method): bool
    {
        return $this->hasAnyTerminatingCalls($method->getDeclaringClass()->getName(), $method->getName());
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     */
    private function findAnyTerminatingCallsInMethodCalls(string $className, string $methodName, array &$alreadyFound = []): bool
    {
        if (isset($alreadyFound[$className][$methodName])) {
            return false; // stop recursion
        } else {
            $alreadyFound[$className][$methodName] = true;
        }

        $hasTerminatingCalls = $this->hasTerminatingCalls[$className][$methodName] ?? false;

        $methodCalls = $this->findCalled($className, $methodName);
        foreach ($methodCalls as $calledMethod) {
            $hasTerminatingCalls = $hasTerminatingCalls || $this->findAnyTerminatingCallsInMethodCalls($calledMethod->getCalledClassName(), $calledMethod->getCalledMethodName(), $alreadyFound);
        }

        return $hasTerminatingCalls;
    }

    public function hasAnyOutputCalls(string $className, string $methodName): bool
    {
        return $this->findAnyOutputCallsInMethodCalls($className, $methodName);
    }

    public function hasAnyOutputCallsByMethod(ReflectionMethod $method): bool
    {
        return $this->hasAnyOutputCalls($method->getDeclaringClass()->getName(), $method->getName());
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     */
    private function findAnyOutputCallsInMethodCalls(string $className, string $methodName, array &$alreadyFound = []): bool
    {
        if (isset($alreadyFound[$className][$methodName])) {
            return false; // stop recursion
        } else {
            $alreadyFound[$className][$methodName] = true;
        }

        $hasTerminatingCalls = $this->hasOutputCalls[$className][$methodName] ?? false;

        $methodCalls = $this->findCalled($className, $methodName);
        foreach ($methodCalls as $calledMethod) {
            $hasTerminatingCalls = $hasTerminatingCalls || $this->findAnyOutputCallsInMethodCalls($calledMethod->getCalledClassName(), $calledMethod->getCalledMethodName(), $alreadyFound);
        }

        return $hasTerminatingCalls;
    }
}
