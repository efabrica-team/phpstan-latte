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

    /**
     * @return class-string|null
     */
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
    public function findCalled(string $className, string $methodName, string $currentClassName = null): array
    {
        $declaringClass = $this->getDeclaringClass($className, $methodName);
        if (!$declaringClass) {
            return [];
        }
        $calledMethods = $this->collectedMethodCalled[$declaringClass][$methodName] ?? [];
        $result = [];
        foreach ($calledMethods as $calledMethod) {
            $calledMethod = $calledMethod->withCurrentClass($this->reflectionProvider->getClass($currentClassName ?? $className));
            if ($this->lattePhpDocResolver->resolveForMethod($calledMethod->getCalledClassName(), $calledMethod->getCalledMethodName())->isIgnored()) {
                continue;
            }
            $result[] = $calledMethod;
        }
        return $result;
    }

    /**
     * @template T
     * @param callable(class-string, string, array<T[]>): T[] $callback
     * @return T[]
     */
    public function traverseCalled(callable $callback, string $className, string $methodName, string $currentClassName = null): array
    {
        return $this->traverseInMethodCalls($callback, $className, $methodName, $currentClassName);
    }

    /**
     * @template T
     * @param callable(class-string, string, array<T[]>): T[] $callback
     * @param array<string, array<string, true>> $alreadyFound
     * @return T[]
     */
    private function traverseInMethodCalls(callable $callback, string $className, string $methodName, string $currentClassName = null, array &$alreadyFound = []): array
    {
        $declaringClass = $this->getDeclaringClass($className, $methodName);
        if (!$declaringClass) {
            return [];
        }

        if (isset($alreadyFound[$declaringClass][$methodName])) {
            return []; // stop recursion
        } else {
            $alreadyFound[$declaringClass][$methodName] = true;
        }

        $fromCalled = [];
        foreach ($this->findCalledOfType($className, $methodName, CollectedMethodCall::CALL, $currentClassName) as $calledMethod) {
            $fromCalled[] = $this->traverseInMethodCalls(
                $callback,
                $calledMethod->getCalledClassName(),
                $calledMethod->getCalledMethodName(),
                $calledMethod->getCurrentClassName(),
                $alreadyFound
            );
        }

        return $callback($declaringClass, $methodName, $fromCalled);
    }

    /**
     * @return CollectedMethodCall[]
     */
    public function findCalledOfType(string $className, string $methodName, string $type, string $currentClassName = null): array
    {
        $calledByType = [];
        foreach ($this->findCalled($className, $methodName, $currentClassName) as $called) {
            if ($called->getType() === $type) {
                $calledByType[] = $called;
            }
        }
        return $calledByType;
    }

    public function hasAnyTerminatingCalls(string $className, string $methodName, string $currentClassName = null): bool
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled) {
            return array_merge([$this->hasTerminatingCalls[$declaringClass][$methodName] ?? false], ...$fromCalled);
        };
        $hasTerminatingCalls = $this->traverseCalled($callback, $className, $methodName, $currentClassName);
        return in_array(true, $hasTerminatingCalls, true);
    }

    public function hasAnyOutputCalls(string $className, string $methodName, string $currentClassName = null): bool
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled) {
            return array_merge([$this->hasOutputCalls[$declaringClass][$methodName] ?? false], ...$fromCalled);
        };
        $hasOutputCalls = $this->traverseCalled($callback, $className, $methodName, $currentClassName);
        return in_array(true, $hasOutputCalls, true);
    }
}
