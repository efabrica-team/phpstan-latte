<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedMethodCall;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use PHPStan\Reflection\ReflectionProvider;

final class MethodCallFinder
{
    private ReflectionProvider $reflectionProvider;

    private LattePhpDocResolver $lattePhpDocResolver;

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

    public function __construct(LatteContextData $latteContext, ReflectionProvider $reflectionProvider, LattePhpDocResolver $lattePhpDocResolver)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->lattePhpDocResolver = $lattePhpDocResolver;

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

    public function getDeclaringClass(string $className, string $methodName): ?string
    {
        $classReflection = $this->reflectionProvider->getClass($className);
        if (!$classReflection->hasNativeMethod($methodName)) {
            return null;
        }
        return $classReflection->getNativeMethod($methodName)->getDeclaringClass()->getName();
    }

    /**
     * @return CollectedMethodCall[]
     */
    public function findCalled(string $className, string $methodName): array
    {
        $declaringClass = $this->getDeclaringClass($className, $methodName);
        if (!$declaringClass) {
            return [];
        }
        $calledMethods = $this->collectedMethodCalled[$declaringClass][$methodName] ?? [];
        $result = [];
        foreach ($calledMethods as $calledMethod) {
            $calledMethod = $calledMethod->withCurrentClassName($className);
            if ($this->lattePhpDocResolver->resolveForMethod($calledMethod->getCalledClassName(), $calledMethod->getCalledMethodName())->isIgnored()) {
                continue;
            }
            $result[] = $calledMethod;
        }
        return $result;
    }

    /**
     * @return CollectedMethodCall[]
     */
    public function findCalledOfType(string $className, string $methodName, string $type): array
    {
        $calledByType = [];
        foreach ($this->findCalled($className, $methodName) as $called) {
            if ($called->getType() === $type) {
                $calledByType[] = $called;
            }
        }
        return $calledByType;
    }

    public function hasAnyTerminatingCalls(string $className, string $methodName): bool
    {
        return $this->findAnyTerminatingCallsInMethodCalls($className, $methodName);
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     */
    private function findAnyTerminatingCallsInMethodCalls(string $className, string $methodName, array &$alreadyFound = []): bool
    {
        $declaringClass = $this->getDeclaringClass($className, $methodName);
        if (!$declaringClass) {
            return false;
        }

        if (isset($alreadyFound[$declaringClass][$methodName])) {
            return false; // stop recursion
        } else {
            $alreadyFound[$declaringClass][$methodName] = true;
        }

        $hasTerminatingCalls = $this->hasTerminatingCalls[$declaringClass][$methodName] ?? false;

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

    /**
     * @param array<string, array<string, true>> $alreadyFound
     */
    private function findAnyOutputCallsInMethodCalls(string $className, string $methodName, array &$alreadyFound = []): bool
    {
        $declaringClass = $this->getDeclaringClass($className, $methodName);
        if (!$declaringClass) {
            return false;
        }

        if (isset($alreadyFound[$declaringClass][$methodName])) {
            return false; // stop recursion
        } else {
            $alreadyFound[$declaringClass][$methodName] = true;
        }

        $hasTerminatingCalls = $this->hasOutputCalls[$declaringClass][$methodName] ?? false;

        $methodCalls = $this->findCalled($className, $methodName);
        foreach ($methodCalls as $calledMethod) {
            $hasTerminatingCalls = $hasTerminatingCalls || $this->findAnyOutputCallsInMethodCalls($calledMethod->getCalledClassName(), $calledMethod->getCalledMethodName(), $alreadyFound);
        }

        return $hasTerminatingCalls;
    }
}
