<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\Helper\FormFieldHelper;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\Form\CollectedFormField;
use Efabrica\PHPStanLatte\Template\Form\FormField;
use PHPStan\BetterReflection\BetterReflection;

final class FormFieldFinder
{
    /**
     * @var array<string, array<string, FormField[]>>
     */
    private array $assignedFormFields = [];

    private MethodCallFinder $methodCallFinder;

    public function __construct(LatteContextData $latteContext, MethodCallFinder $methodCallFinder)
    {
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
     * @return FormField[]
     */
    public function find(string $className, string $methodName): array
    {
        return FormFieldHelper::merge(
            $this->findInClasses($className),
            $this->findInMethodCalls($className, '__construct'),
            $this->findInMethodCalls($className, $methodName),
        );
    }

    /**
     * @return FormField[]
     */
    private function findInClasses(string $className): array
    {
        $classReflection = (new BetterReflection())->reflector()->reflectClass($className);

        $assignedFormFields = $this->assignedFormFields[$className][''] ?? [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $assignedFormFields = FormFieldHelper::union($this->assignedFormFields[$parentClass][''] ?? [], $assignedFormFields);
        }
        return $assignedFormFields;
    }

    /**
     * @return FormField[]
     */
    private function findInMethodCalls(string $className, string $methodName, string $currentClassName = null): array
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled) {
            return FormFieldHelper::union($this->assignedFormFields[$declaringClass][$methodName] ?? [], ...$fromCalled);
        };
        return $this->methodCallFinder->traverseCalled($callback, $className, $methodName, $currentClassName);
    }
}
