<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\Form\CollectedFormField;
use Efabrica\PHPStanLatte\Template\Form\Field;
use Efabrica\PHPStanLatte\Template\ItemCombinator;
use PHPStan\Reflection\ReflectionProvider;

final class FormFieldFinder
{
    /**
     * @var array<string, array<string, Field[]>>
     */
    private array $assignedFormFields = [];

    private ReflectionProvider $reflectionProvider;

    private MethodCallFinder $methodCallFinder;

    public function __construct(LatteContextData $latteContext, ReflectionProvider $reflectionProvider, MethodCallFinder $methodCallFinder)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->methodCallFinder = $methodCallFinder;

        $collectedForms = $latteContext->getCollectedData(CollectedFormField::class);
        foreach ($collectedForms as $collectedFormField) {
            $className = $collectedFormField->getClassName();
            $methodName = $collectedFormField->getMethodName();
            if (!isset($this->assignedFormFields[$className][$methodName])) {
                $this->assignedFormFields[$className][$methodName] = [];
            }
            $this->assignedFormFields[$className][$methodName][] = $collectedFormField->getFormField();
        }
    }

    /**
     * @return Field[]
     */
    public function find(string $className, string ...$methodNames): array
    {
        $foundFormFields = [
            $this->findInClasses($className),
            $this->findInMethodCalls($className, '__construct'),
        ];
        foreach ($methodNames as $methodName) {
            $foundFormFields[] = $this->findInMethodCalls($className, $methodName);
        }
        return ItemCombinator::merge(...$foundFormFields);
    }

    /**
     * @return Field[]
     */
    private function findInClasses(string $className): array
    {
        $classReflection = $this->reflectionProvider->getClass($className);

        $assignedFormFields = $this->assignedFormFields[$className][''] ?? [];
        foreach ($classReflection->getParentClassesNames() as $parentClass) {
            $assignedFormFields = ItemCombinator::union($this->assignedFormFields[$parentClass][''] ?? [], $assignedFormFields);
        }
        return $assignedFormFields;
    }

    /**
     * @return Field[]
     */
    private function findInMethodCalls(string $className, string $methodName, string $currentClassName = null): array
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled) {
            /** @var array<Field[]> $fromCalled */
            /** @var Field[] $formFields */
            $formFields = $this->assignedFormFields[$declaringClass][$methodName] ?? [];
            return ItemCombinator::union($formFields, ...$fromCalled);
        };
        return $this->methodCallFinder->traverseCalled($callback, $className, $methodName, $currentClassName);
    }
}
