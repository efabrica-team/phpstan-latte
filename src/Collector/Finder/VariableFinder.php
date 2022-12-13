<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedVariable;
use Efabrica\PHPStanLatte\Collector\VariableCollector;
use Efabrica\PHPStanLatte\Collector\VariableMethodPhpDocCollector;
use Efabrica\PHPStanLatte\Helper\VariablesHelper;
use Efabrica\PHPStanLatte\Template\Variable;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
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
    private array $assignedVariables = [];

    /**
     * @var array<string, array<string, Variable[]>>
     */
    private array $declaredVariables = [];

    private MethodCallFinder $methodCallFinder;

    public function __construct(CollectedDataNode $collectedDataNode, TypeSerializer $typeSerializer, MethodCallFinder $methodCallFinder)
    {
        $this->methodCallFinder = $methodCallFinder;

        $assignedVariables = VariableCollector::loadData($collectedDataNode, $typeSerializer, CollectedVariable::class);
        foreach ($assignedVariables as $variable) {
            $className = $variable->getClassName();
            $methodName = $variable->getMethodName();
            $this->assignedVariables[$className][$methodName] = VariablesHelper::union($this->assignedVariables[$className][$methodName] ?? [], [$variable->getVariable()]);
        }

        $declaredVariables = VariableMethodPhpDocCollector::loadData($collectedDataNode, $typeSerializer, CollectedVariable::class);
        foreach ($declaredVariables as $variable) {
            $className = $variable->getClassName();
            $methodName = $variable->getMethodName();
            $this->declaredVariables[$className][$methodName] = VariablesHelper::merge($this->declaredVariables[$className][$methodName] ?? [], [$variable->getVariable()]);
        }
    }

    /**
     * @return Variable[]
     */
    public function find(string $className, string $methodName): array
    {
        file_put_contents("/app/trait.log", print_r([$className, $methodName, VariablesHelper::merge(
            $this->findInClasses($className),
            $this->findInMethodCalls($className, '__construct'),
            $this->findInMethodCalls($className, $methodName)
        )], true), FILE_APPEND);
        return VariablesHelper::merge(
            $this->findInClasses($className),
            $this->findInMethodCalls($className, '__construct'),
            $this->findInMethodCalls($className, $methodName)
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
    private function findInClasses(string $className)
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
    private function findInMethodCalls(string $className, string $methodName, array &$alreadyFound = []): array
    {
        if (isset($alreadyFound[$className][$methodName])) {
            return []; // stop recursion
        } else {
            $alreadyFound[$className][$methodName] = true;
        }

        $collectedVariables = $this->assignedVariables[$className][$methodName] ?? [];

        $methodCalls = $this->methodCallFinder->findCalled($className, $methodName);
        foreach ($methodCalls as $calledClassName => $calledMethods) {
            foreach ($calledMethods as $calledMethod) {
                $collectedVariables = VariablesHelper::union($collectedVariables, $this->findInMethodCalls($calledClassName, $calledMethod, $alreadyFound));
            }
        }

        $collectedVariables = VariablesHelper::merge($collectedVariables, $this->declaredVariables[$className][$methodName] ?? []);

        return $collectedVariables;
    }
}
