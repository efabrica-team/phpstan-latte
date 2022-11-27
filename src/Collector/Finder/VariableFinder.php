<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedVariable;
use Efabrica\PHPStanLatte\Collector\VariableCollector;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\Node\CollectedDataNode;
use PHPStan\PhpDoc\TypeStringResolver;

/**
 * @phpstan-import-type CollectedVariableArray from CollectedVariable
 */
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
        return $this->findMethodCalls($className, $methodName);
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     * @return Variable[]
     */
    private function findMethodCalls(string $className, string $methodName, array &$alreadyFound = []): array
    {
        if (isset($alreadyFound[$className][$methodName])) {
            return []; // stop recursion
        } else {
            $alreadyFound[$className][$methodName] = true;
        }

        // TODO check not only called method but also all parents

        $collectedVariables = [
            $this->collectedVariables[$className][$methodName] ?? [],
        ];

        $methodCalls = $this->methodCallFinder->find($className, $methodName);
        foreach ($methodCalls as $calledClassName => $calledMethods) {
            $collectedVariables[] = $this->findMethodCalls($calledClassName, '', $alreadyFound);
            foreach ($calledMethods as $calledMethod) {
                $collectedVariables[] = $this->findMethodCalls($calledClassName, $calledMethod, $alreadyFound);
            }
        }

        return array_merge(...$collectedVariables);
    }

    /**
     * @phpstan-param array<CollectedVariableArray> $data
     * @return CollectedVariable[]
     */
    private function buildData(array $data): array
    {
        $collectedVariables = [];
        foreach ($data as $item) {
            $variable = new Variable($item['variableName'], $this->typeStringResolver->resolve($item['variableType']));
            $item = new CollectedVariable($item['className'], $item['methodName'], $variable);
            $collectedVariables[] = $item;
        }
        return $collectedVariables;
    }
}
