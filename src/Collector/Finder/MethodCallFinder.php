<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\MethodCallCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedMethodCall;
use PHPStan\Node\CollectedDataNode;

final class MethodCallFinder
{
    /**
     * @var array<string, array<string, array<string, string[]>>>
     */
    private array $collectedMethodCalls;

    public function __construct(CollectedDataNode $collectedDataNode)
    {
        $collectedMethodCalls = $this->buildData(array_filter(array_merge(...array_values($collectedDataNode->get(MethodCallCollector::class)))));
        foreach ($collectedMethodCalls as $collectedMethodCall) {
            $callerClassName = $collectedMethodCall->getCallerClassName();
            $callerMethodName = $collectedMethodCall->getCallerMethodName();
            $calledClassName = $collectedMethodCall->getCalledClassName();
            if (!isset($this->collectedMethodCalls[$callerClassName][$callerMethodName][$calledClassName])) {
                $this->collectedMethodCalls[$callerClassName][$callerMethodName][$calledClassName] = [];
            }
            $this->collectedMethodCalls[$callerClassName][$callerMethodName][$calledClassName][] = $collectedMethodCall->getCalledMethodName();
        }
    }

    /**
     * @return array<string, string[]>
     */
    public function find(string $className, string $methodName): array
    {
        return $this->collectedMethodCalls[$className][$methodName] ?? [];
    }

    /**
     * @param array<CollectedMethodCall|array{callerClassName: string, callerMethodName: string, calledClassName: string, calledMethodName: string}> $data
     * @return CollectedMethodCall[]
     */
    private function buildData(array $data): array
    {
        $collectedMethodCalls = [];
        foreach ($data as $item) {
            if (!$item instanceof CollectedMethodCall) {
                $item = new CollectedMethodCall(...array_values($item));
            }
            $collectedMethodCalls[] = $item;
        }
        return $collectedMethodCalls;
    }
}
