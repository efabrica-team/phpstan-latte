<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\MethodCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedMethod;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Node\CollectedDataNode;

/**
 * @phpstan-import-type CollectedMethodArray from CollectedMethod
 */
final class MethodFinder
{
    /**
     * @var array<string, array<string, CollectedMethod>>>
     */
    private array $collectedMethods;

    private MethodCallFinder $methodCallFinder;

    public function __construct(CollectedDataNode $collectedDataNode, MethodCallFinder $methodCallFinder)
    {
        $this->methodCallFinder = $methodCallFinder;

        $collectedMethods = MethodCollector::loadData($collectedDataNode, CollectedMethod::class);
        foreach ($collectedMethods as $collectedMethod) {
            $this->collectedMethods[$collectedMethod->getClassName()][$collectedMethod->getMethodName()] = $collectedMethod;
        }
    }

    public function find(string $className, string $methodName): CollectedMethod
    {
        return $this->collectedMethods[$className][$methodName] ?? CollectedMethod::unknown($className, $methodName);
    }

    public function findByMethod(ReflectionMethod $method): CollectedMethod
    {
        return $this->find($method->getDeclaringClass()->getName(), $method->getName());
    }

    public function hasAnyAlwaysTerminated(string $className, string $methodName): bool
    {
        return $this->findAnyAlwaysTerminatedInMethodCalls($className, $methodName);
    }

    public function hasAnyAlwaysTerminatedByMethod(ReflectionMethod $method): bool
    {
        return $this->hasAnyAlwaysTerminated($method->getDeclaringClass()->getName(), $method->getName());
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     */
    private function findAnyAlwaysTerminatedInMethodCalls(string $className, string $methodName, array &$alreadyFound = []): bool
    {
        if (isset($alreadyFound[$className][$methodName])) {
            return false; // stop recursion
        } else {
            $alreadyFound[$className][$methodName] = true;
        }

        $alwaysTerminated = $this->find($className, $methodName)->isAlwaysTerminated();

        $methodCalls = $this->methodCallFinder->findCalled($className, $methodName);
        foreach ($methodCalls as $calledClassName => $calledMethods) {
            foreach ($calledMethods as $calledMethod) {
                $alwaysTerminated = $alwaysTerminated || $this->findAnyAlwaysTerminatedInMethodCalls($calledClassName, $calledMethod, $alreadyFound);
            }
        }

        return $alwaysTerminated;
    }
}
