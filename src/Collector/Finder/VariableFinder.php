<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedVariable;
use Efabrica\PHPStanLatte\Collector\VariableCollector;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\Node\CollectedDataNode;
use PHPStan\PhpDoc\TypeStringResolver;

final class VariableFinder
{
    /**
     * @var array<string, array<string, Variable[]>>
     */
    private array $collectedVariables;

    private MethodCallFinder $methodCallFinder;

    private TypeStringResolver $typeStringResolver;

    public function __construct(CollectedDataNode $collectedDataNode, MethodCallFinder $methodCallFinder, TypeStringResolver $typeStringResolver)
    {
        $this->methodCallFinder = $methodCallFinder;
        $this->typeStringResolver = $typeStringResolver;

        $collectedVariables = $this->buildData(array_filter(array_merge(...array_values($collectedDataNode->get(VariableCollector::class)))));
        foreach ($collectedVariables as $collectedVariable) {
            $className = $collectedVariable->getClassName();
            $methodName = $collectedVariable->getMethodName();
            if (!isset($this->collectedVariables[$className][$methodName])) {
                $this->collectedVariables[$className][$methodName] = [];
            }
            $this->collectedVariables[$className][$methodName][] = $collectedVariable->getVariable();
        }
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

    /**
     * @param array<CollectedVariable|array{className: string, methodName: string, variable: array{name: string, type: string}}> $data
     * @return CollectedVariable[]
     */
    private function buildData(array $data): array
    {
        $collectedVariables = [];
        foreach ($data as $item) {
            if (!$item instanceof CollectedVariable) {
                $item['variable'] = new Variable($item['variable']['name'], $this->typeStringResolver->resolve($item['variable']['type']));
                $item = new CollectedVariable(...array_values($item));
            }
            $collectedVariables[] = $item;
        }
        return $collectedVariables;
    }
}
