<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\MethodCallCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedMethodCall;
use PHPStan\Node\CollectedDataNode;

/**
 * @phpstan-import-type CollectedMethodCallArray from CollectedMethodCall
 */
final class MethodCallFinder
{
    /**
     * @var array<string, array<string, array<string, string[]>>>
     */
    private array $collectedMethodCalled;

    /**
     * @var array<string, array<string, array<string, string[]>>>
     */
    private array $collectedMethodCallers;

    public function __construct(CollectedDataNode $collectedDataNode)
    {
        $collectedMethodCalls = $this->buildData(array_filter(array_merge(...array_values($collectedDataNode->get(MethodCallCollector::class)))));
        foreach ($collectedMethodCalls as $collectedMethodCall) {
            $callerClassName = $collectedMethodCall->getCallerClassName();
            $callerMethodName = $collectedMethodCall->getCallerMethodName();
            $calledClassName = $collectedMethodCall->getCalledClassName();
            $calledMethodName = $collectedMethodCall->getCalledMethodName();
            if (!isset($this->collectedMethodCalled[$callerClassName][$callerMethodName][$calledClassName])) {
                $this->collectedMethodCalled[$callerClassName][$callerMethodName][$calledClassName] = [];
            }
            $this->collectedMethodCalled[$callerClassName][$callerMethodName][$calledClassName][] = $calledMethodName;
            if (!isset($this->collectedMethodCallers[$calledClassName][$calledMethodName][$callerClassName])) {
                $this->collectedMethodCallers[$calledClassName][$calledMethodName][$callerClassName] = [];
            }
            $this->collectedMethodCallers[$calledClassName][$calledMethodName][$callerClassName][] = $callerMethodName;
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

    /**
     * @phpstan-param array<CollectedMethodCallArray> $data
     * @return CollectedMethodCall[]
     */
    private function buildData(array $data): array
    {
        $collectedMethodCalls = [];
        foreach ($data as $item) {
            $collectedMethodCalls[] = CollectedMethodCall::fromArray($item);
        }
        return $collectedMethodCalls;
    }
}
