<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedVariable;
use Efabrica\PHPStanLatte\Collector\VariableCollector;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\Node\CollectedDataNode;

final class VariableFinder
{
    /**
     * @var array<string, array<string, Variable[]>>
     */
    private array $collectedVariables;

    private MethodCallFinder $methodCallFinder;

    public function __construct(CollectedDataNode $collectedDataNode, MethodCallFinder $methodCallFinder)
    {
        /** @var CollectedVariable[] $collectedVariables */
        $collectedVariables = array_merge(...array_values($collectedDataNode->get(VariableCollector::class)));
        foreach ($collectedVariables as $collectedVariable) {
            $className = $collectedVariable->getClassName();
            $methodName = $collectedVariable->getMethodName();
            if (!isset($this->collectedVariables[$className][$methodName])) {
                $this->collectedVariables[$className][$methodName] = [];
            }
            $this->collectedVariables[$className][$methodName][] = $collectedVariable->getVariable();
        }
        $this->methodCallFinder = $methodCallFinder;
    }

    /**
     * @return Variable[]
     */
    public function find(string $className, string $methodName): array
    {
        $collectedVariables = [
            $this->collectedVariables[$className][$methodName] ?? [],
        ];

        $methodCalls = $this->methodCallFinder->find($className, $methodName);
        foreach ($methodCalls as $calledClassName => $calledMethods) {
            foreach ($calledMethods as $calledMethod) {
                $collectedVariables[] = $this->find($calledClassName, $calledMethod);
            }
        }

        // TODO merge types of collected variables

        return array_merge(...$collectedVariables);
    }
}
