<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\Form\CollectedForm;
use Efabrica\PHPStanLatte\Template\Form\Form;
use PHPStan\Reflection\ReflectionProvider;

final class FormFinder
{
    /**
     * @var array<string, array<string, CollectedForm[]>>
     */
    private array $collectedForms = [];

    private ReflectionProvider $reflectionProvider;

    private MethodCallFinder $methodCallFinder;

    private FormFieldFinder $formFieldFinder;

    public function __construct(LatteContextData $latteContext, ReflectionProvider $reflectionProvider, MethodCallFinder $methodCallFinder, FormFieldFinder $formFieldFinder)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->methodCallFinder = $methodCallFinder;
        $this->formFieldFinder = $formFieldFinder;

        $collectedForms = $latteContext->getCollectedData(CollectedForm::class);
        foreach ($collectedForms as $collectedForm) {
            $className = $collectedForm->getClassName();
            $methodName = $collectedForm->getMethodName();
            if (!isset($this->collectedForms[$className][$methodName])) {
                $this->collectedForms[$className][$methodName] = [];
            }
            $this->collectedForms[$className][$methodName][] = $collectedForm;
        }
    }

    /**
     * @return Form[]
     */
    public function find(string $className, string ...$methodNames): array
    {
        $foundForms = [
            $this->findInClasses($className),
            $this->findInMethodCalls($className, '__construct'),
        ];
        foreach ($methodNames as $methodName) {
            $foundForms[] = $this->findInMethodCalls($className, $methodName);
        }
        /** @var CollectedForm[] $collectedForms */
        $collectedForms = array_merge(...$foundForms);

        $forms = [];
        foreach ($collectedForms as $collectedForm) {
            $createdClassName = $collectedForm->getCreatedClassName();
            $parentClassNames = $this->reflectionProvider->getClass($className)->getParentClassesNames();
            if (in_array($createdClassName, $parentClassNames, true)) {
                $createdClassName = $className;
            }
            $formFields = $this->formFieldFinder->find(
                $createdClassName,
                $collectedForm->getCreatedMethodName()
            );
            $forms[$collectedForm->getForm()->getName()] = $collectedForm->getForm()->withControls($formFields);
        }

        return $forms;
    }

    /**
     * @return CollectedForm[]
     */
    private function findInClasses(string $className): array
    {
        $classReflection = $this->reflectionProvider->getClass($className);

        $collectedForms = $this->collectedForms[$className][''] ?? [];
        foreach ($classReflection->getParentClassesNames() as $parentClass) {
            $collectedForms = array_merge($this->collectedForms[$parentClass][''] ?? [], $collectedForms);
        }
        return $collectedForms;
    }

    /**
     * @return CollectedForm[]
     */
    private function findInMethodCalls(string $className, string $methodName, string $currentClassName = null): array
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled) {
            return array_merge($this->collectedForms[$declaringClass][$methodName] ?? [], ...$fromCalled);
        };
        return $this->methodCallFinder->traverseCalled($callback, $className, $methodName, $currentClassName);
    }
}
