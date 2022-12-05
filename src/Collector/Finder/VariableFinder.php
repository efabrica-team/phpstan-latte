<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedVariable;
use Efabrica\PHPStanLatte\Collector\VariableCollector;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Node\CollectedDataNode;

/**
 * @phpstan-import-type CollectedVariableArray from CollectedVariable
 */
final class VariableFinder
{
    /**
     * @var array<string, array<string, Variable[]>>
     */
    private array $collectedVariables = [];

    private MethodCallFinder $methodCallFinder;

    public function __construct(CollectedDataNode $collectedDataNode, MethodCallFinder $methodCallFinder)
    {
        $this->methodCallFinder = $methodCallFinder;

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
        return array_merge(
            $this->collectedVariables[$className][''] ?? [],
            $this->findInParents($className),
            $this->findInMethodCalls($className, '__construct'),
            $this->findInMethodCalls($className, $methodName),
        );
    }

    /**
     * @return Variable[]
     */
    public function findByMethod(ReflectionMethod $method): array
    {
        return $this->find($method->getDeclaringClass()->getName(), $method->getName());
    }

    /**
     * @return Variable[]
     */
    private function findInParents(string $className)
    {
        $classReflection = (new BetterReflection())->reflector()->reflectClass($className);

        $collectedVariables = [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $collectedVariables = array_merge(
                $this->collectedVariables[$parentClass][''] ?? [],
                $collectedVariables
            );
        }
        return $collectedVariables;
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     * @return Variable[]
     */
    private function findInMethodCalls(string $className, string $methodName, array &$alreadyFound = []): array
    {
        if (isset($alreadyFound[$className][$methodName])) {
            return []; // stop recursion
        } else {
            $alreadyFound[$className][$methodName] = true;
        }

        $collectedVariables = [
            $this->collectedVariables[$className][$methodName] ?? [],
        ];

        $methodCalls = $this->methodCallFinder->findCalled($className, $methodName);
        foreach ($methodCalls as $calledClassName => $calledMethods) {
            foreach ($calledMethods as $calledMethod) {
                $collectedVariables[] = $this->findInMethodCalls($calledClassName, $calledMethod, $alreadyFound);
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
            $collectedVariables[] = CollectedVariable::fromArray($item);
        }
        return $collectedVariables;
    }
}
