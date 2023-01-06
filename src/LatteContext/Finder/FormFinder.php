<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\Form\CollectedForm;
use Efabrica\PHPStanLatte\Template\Form\Form;
use PHPStan\BetterReflection\BetterReflection;

final class FormFinder
{
    /**
     * @var array<string, array<string, CollectedForm[]>>
     */
    private array $collectedForms = [];

    private MethodCallFinder $methodCallFinder;

    private FormFieldFinder $formFieldFinder;

    public function __construct(LatteContextData $latteContext, MethodCallFinder $methodCallFinder, FormFieldFinder $formFieldFinder)
    {
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
    public function find(string $className, string $methodName): array
    {
        /** @var CollectedForm[] $collectedForms */
        $collectedForms = array_merge(
            $this->findInClasses($className),
            $this->findInMethodCalls($className, '__construct'),
            $this->findInMethodCalls($className, $methodName),
        );

        $forms = [];
        foreach ($collectedForms as $collectedForm) {
            $createdClassName = $collectedForm->getCreatedClassName();
            $parentClassNames = (new BetterReflection())->reflector()->reflectClass($className)->getParentClassNames();
            if (in_array($createdClassName, $parentClassNames)) {
                $createdClassName = $className;
            }
            $formFields = $this->formFieldFinder->find(
                $createdClassName,
                $collectedForm->getCreatedMethodName()
            );
            $forms[$collectedForm->getForm()->getName()] = $collectedForm->getForm()->withFields($formFields);
        }

        return $forms;
    }

    /**
     * @return CollectedForm[]
     */
    private function findInClasses(string $className): array
    {
        $classReflection = (new BetterReflection())->reflector()->reflectClass($className);

        $collectedForms = $this->collectedForms[$className][''] ?? [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $collectedForms = array_merge($this->collectedForms[$parentClass][''] ?? [], $collectedForms);
        }
        return $collectedForms;
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     * @return CollectedForm[]
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

        $collectedForms = [
            $this->collectedForms[$declaringClass][$methodName] ?? [],
        ];

        $methodCalls = $this->methodCallFinder->findCalled($className, $methodName, $currentClassName);
        foreach ($methodCalls as $calledMethod) {
            $collectedForms[] = $this->findInMethodCalls(
                $calledMethod->getCalledClassName(),
                $calledMethod->getCalledMethodName(),
                $calledMethod->getCurrentClassName(),
                $alreadyFound
            );
        }

        return array_merge(...$collectedForms);
    }
}
