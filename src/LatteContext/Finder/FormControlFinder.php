<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\Form\CollectedFormControl;
use Efabrica\PHPStanLatte\Template\Form\ControlInterface;
use Efabrica\PHPStanLatte\Template\ItemCombinator;
use PHPStan\Reflection\ReflectionProvider;

final class FormControlFinder
{
    /** @var array<string, array<string, ControlInterface[]>> */
    private array $assignedFormControls = [];

    private ReflectionProvider $reflectionProvider;

    private MethodCallFinder $methodCallFinder;

    public function __construct(LatteContextData $latteContext, ReflectionProvider $reflectionProvider, MethodCallFinder $methodCallFinder)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->methodCallFinder = $methodCallFinder;

        $collectedFormControls = $latteContext->getCollectedData(CollectedFormControl::class);

        /** @var CollectedFormControl $collectedFormControl */
        foreach ($collectedFormControls as $collectedFormControl) {
            $className = $collectedFormControl->getClassName();
            $methodName = $collectedFormControl->getMethodName();
            if (!isset($this->assignedFormControls[$className][$methodName])) {
                $this->assignedFormControls[$className][$methodName] = [];
            }
            $this->assignedFormControls[$className][$methodName][] = $collectedFormControl->getFormControl();
        }
    }

    /**
     * @param class-string $className
     * @return ControlInterface[]
     */
    public function find(string $className, string ...$methodNames): array
    {
        $foundFormControls = [
            $this->findInClasses($className),
            $this->findInMethodCalls($className, '__construct'),
        ];
        foreach ($methodNames as $methodName) {
            $foundFormControls[] = $this->findInMethodCalls($className, $methodName);
        }
        return ItemCombinator::merge(...$foundFormControls);
    }

    /**
     * @return ControlInterface[]
     */
    private function findInClasses(string $className): array
    {
        $classReflection = $this->reflectionProvider->getClass($className);

        $assignedFormControls = $this->assignedFormControls[$className][''] ?? [];
        foreach ($classReflection->getParentClassesNames() as $parentClass) {
            $assignedFormControls = ItemCombinator::union($this->assignedFormControls[$parentClass][''] ?? [], $assignedFormControls);
        }
        return $assignedFormControls;
    }

    /**
     * @param class-string $className
     * @param ?class-string $currentClassName
     * @return ControlInterface[]
     */
    private function findInMethodCalls(string $className, string $methodName, ?string $currentClassName = null): array
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled, ?string $currentClassName) {
            /** @var array<ControlInterface[]> $fromCalled */
            /** @var ControlInterface[] $formControls */
            $formControls = ItemCombinator::resolveTemplateTypes(
                $this->assignedFormControls[$declaringClass][$methodName] ?? [],
                $declaringClass,
                $currentClassName
            );
            return ItemCombinator::union($formControls, ...$fromCalled);
        };
        return $this->methodCallFinder->traverseCalled($callback, $className, $methodName, $currentClassName);
    }
}
