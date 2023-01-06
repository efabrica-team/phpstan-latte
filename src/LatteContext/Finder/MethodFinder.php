<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedMethod;

final class MethodFinder
{
    /**
     * @var array<string, array<string, CollectedMethod>>>
     */
    private array $collectedMethods;

    private MethodCallFinder $methodCallFinder;

    public function __construct(LatteContextData $latteContext, MethodCallFinder $methodCallFinder)
    {
        $this->methodCallFinder = $methodCallFinder;

        $collectedMethods = $latteContext->getCollectedData(CollectedMethod::class);
        foreach ($collectedMethods as $collectedMethod) {
            $this->collectedMethods[$collectedMethod->getClassName()][$collectedMethod->getMethodName()] = $collectedMethod;
        }
    }

    public function find(string $className, string $methodName): CollectedMethod
    {
        return $this->collectedMethods[$className][$methodName] ?? CollectedMethod::unknown($className, $methodName);
    }

    public function hasAnyAlwaysTerminated(string $className, string $methodName): bool
    {
        return $this->findAnyAlwaysTerminatedInMethodCalls($className, $methodName);
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     */
    private function findAnyAlwaysTerminatedInMethodCalls(string $className, string $methodName, string $currentClassName = null, array &$alreadyFound = []): bool
    {
        if (isset($alreadyFound[$className][$methodName])) {
            return false; // stop recursion
        } else {
            $alreadyFound[$className][$methodName] = true;
        }

        $alwaysTerminated = $this->find($className, $methodName)->isAlwaysTerminated();

        $methodCalls = $this->methodCallFinder->findCalled($className, $methodName, $currentClassName);
        foreach ($methodCalls as $calledMethod) {
            $alwaysTerminated = $alwaysTerminated || $this->findAnyAlwaysTerminatedInMethodCalls(
                $calledMethod->getCalledClassName(),
                $calledMethod->getCalledMethodName(),
                $calledMethod->getCurrentClassName(),
                $alreadyFound
            );
        }

        return $alwaysTerminated;
    }
}
