<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\Helper\VariablesHelper;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
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
                $this->declaredVariables[$className][$methodName] = VariablesHelper::merge($this->declaredVariables[$className][$methodName] ?? [], [$variable->getVariable()]);
            } else {
                $this->assignedVariables[$className][$methodName] = VariablesHelper::union($this->assignedVariables[$className][$methodName] ?? [], [$variable->getVariable()]);
            }
        }
    }

    /**
     * @return Variable[]
     */
    public function find(string $className, string $methodName): array
    {
        return VariablesHelper::merge(
            $this->findInClasses($className),
            $this->findInMethodCalls($className, '__construct'),
            $this->findInMethodCalls($className, $methodName)
        );
    }

    /**
     * @return Variable[]
     */
    private function findInClasses(string $className): array
    {
        $classReflection = (new BetterReflection())->reflector()->reflectClass($className);

        $assignedVariables = $this->assignedVariables[$className][''] ?? [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $assignedVariables = VariablesHelper::union($this->assignedVariables[$parentClass][''] ?? [], $assignedVariables);
        }
        $declaredVariables = $this->declaredVariables[$className][''] ?? [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $declaredVariables = VariablesHelper::merge($this->declaredVariables[$parentClass][''] ?? [], $declaredVariables);
        }
        return VariablesHelper::merge($assignedVariables, $declaredVariables);
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     * @return Variable[]
     */
    private function findInMethodCalls(string $className, string $methodName, string $currentClassName = null, array &$alreadyFound = []): array
    {
        $declaringClass = $this->methodCallFinder->getDeclaringClass($className, $methodName);
        if (!$declaringClass) {
            return [];
        }

        if (isset($alreadyFound[$declaringClass][$methodName])) {
            return []; // stop recursion
        } else {
            $alreadyFound[$declaringClass][$methodName] = true;
        }

        $collectedVariables = $this->assignedVariables[$declaringClass][$methodName] ?? [];

        $methodCalls = $this->methodCallFinder->findCalled($className, $methodName, $currentClassName);
        foreach ($methodCalls as $calledMethod) {
            $collectedVariables = VariablesHelper::union($collectedVariables,
                $this->findInMethodCalls(
                    $calledMethod->getCalledClassName(),
                    $calledMethod->getCalledMethodName(),
                    $calledMethod->getCurrentClassName(),
                    $alreadyFound
                )
            );
        }

        $collectedVariables = VariablesHelper::merge($collectedVariables, $this->declaredVariables[$declaringClass][$methodName] ?? []);

        return $collectedVariables;
    }
}
