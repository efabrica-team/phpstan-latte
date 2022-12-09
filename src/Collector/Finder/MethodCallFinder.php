<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\MethodCallCollector;
use Efabrica\PHPStanLatte\Collector\MethodOutputCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedMethodCall;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
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

    /**
     * @var array<string, array<string, bool>>>
     */
    private array $hasOutputCalls = [];

    public function __construct(CollectedDataNode $collectedDataNode, TypeSerializer $typeSerializer)
    {
        $collectedMethodCalls = array_merge(
            MethodCallCollector::loadData($collectedDataNode, $typeSerializer, CollectedMethodCall::class),
            MethodOutputCollector::loadData($collectedDataNode, $typeSerializer, CollectedMethodCall::class)
        );
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
            if ($collectedMethodCall->isOutputCall()) {
                $this->hasOutputCalls[$callerClassName][$callerMethodName] = true;
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
        foreach ($methodCalls as $calledClassName => $calledMethods) {
            foreach ($calledMethods as $calledMethod) {
                $hasTerminatingCalls = $hasTerminatingCalls || $this->findAnyTerminatingCallsInMethodCalls($calledClassName, $calledMethod, $alreadyFound);
            }
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
        foreach ($methodCalls as $calledClassName => $calledMethods) {
            foreach ($calledMethods as $calledMethod) {
                $hasTerminatingCalls = $hasTerminatingCalls || $this->findAnyOutputCallsInMethodCalls($calledClassName, $calledMethod, $alreadyFound);
            }
        }

        return $hasTerminatingCalls;
    }
}
