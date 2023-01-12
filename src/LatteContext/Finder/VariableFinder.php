<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\Template\ItemCombinator;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\BetterReflection\BetterReflection;

final class VariableFinder
{
    /**
     * @var array<string, array<string, Variable[]>>
     */
    private array $assignedVariables = [];

    /**
     * @var array<string, array<string, Variable[]>>
     */
    private array $declaredVariables = [];

    private MethodCallFinder $methodCallFinder;

    public function __construct(LatteContextData $latteContext, MethodCallFinder $methodCallFinder)
    {
        $this->methodCallFinder = $methodCallFinder;

        $collectedVriables = $latteContext->getCollectedData(CollectedVariable::class);
        foreach ($collectedVriables as $variable) {
            $className = $variable->getClassName();
            $methodName = $variable->getMethodName();
            if ($variable->isDeclared()) {
                $this->declaredVariables[$className][$methodName] = ItemCombinator::merge($this->declaredVariables[$className][$methodName] ?? [], [$variable->getVariable()]);
            } else {
                $this->assignedVariables[$className][$methodName] = ItemCombinator::union($this->assignedVariables[$className][$methodName] ?? [], [$variable->getVariable()]);
            }
        }
    }

    /**
     * @return Variable[]
     */
    public function find(string $className, string ...$methodNames): array
    {
        $foundVariables = [
            $this->findInClasses($className),
            $this->findInMethodCalls($className, '__construct'),
        ];
        foreach ($methodNames as $methodName) {
            $foundVariables[] = $this->findInMethodCalls($className, $methodName);
        }
        return ItemCombinator::merge(...$foundVariables);
    }

    /**
     * @return Variable[]
     */
    private function findInClasses(string $className): array
    {
        $classReflection = (new BetterReflection())->reflector()->reflectClass($className);

        $assignedVariables = $this->assignedVariables[$className][''] ?? [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $assignedVariables = ItemCombinator::union($this->assignedVariables[$parentClass][''] ?? [], $assignedVariables);
        }
        $declaredVariables = $this->declaredVariables[$className][''] ?? [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $declaredVariables = ItemCombinator::merge($this->declaredVariables[$parentClass][''] ?? [], $declaredVariables);
        }
        return ItemCombinator::merge($assignedVariables, $declaredVariables);
    }

    /**
     * @return Variable[]
     */
    private function findInMethodCalls(string $className, string $methodName, string $currentClassName = null): array
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled) {
            /** @var array<Variable[]> $fromCalled */
            /** @var Variable[] $collectedVariables */
            $collectedVariables = $this->assignedVariables[$declaringClass][$methodName] ?? [];
            $collectedVariables = ItemCombinator::union($collectedVariables, ...$fromCalled);
            $collectedVariables = ItemCombinator::merge($collectedVariables, $this->declaredVariables[$declaringClass][$methodName] ?? []);
            return $collectedVariables;
        };
        return $this->methodCallFinder->traverseCalled($callback, $className, $methodName, $currentClassName);
    }
}
