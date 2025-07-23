<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\Form\CollectedFormGroup;
use Efabrica\PHPStanLatte\Template\Form\Group;
use Efabrica\PHPStanLatte\Template\ItemCombinator;
use PHPStan\Reflection\ReflectionProvider;

final class FormGroupFinder
{
    /** @var array<string, array<string, Group[]>> */
    private array $assignedGroups = [];

    private ReflectionProvider $reflectionProvider;

    private MethodCallFinder $methodCallFinder;

    public function __construct(LatteContextData $latteContext, ReflectionProvider $reflectionProvider, MethodCallFinder $methodCallFinder)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->methodCallFinder = $methodCallFinder;

        $collectedGroups = $latteContext->getCollectedData(CollectedFormGroup::class);

        /** @var CollectedFormGroup $collectedGroup */
        foreach ($collectedGroups as $collectedGroup) {
            $className = $collectedGroup->getClassName();
            $methodName = $collectedGroup->getMethodName();
            if (!isset($this->assignedGroups[$className][$methodName])) {
                $this->assignedGroups[$className][$methodName] = [];
            }
            $this->assignedGroups[$className][$methodName][] = $collectedGroup->getGroup();
        }
    }

    /**
     * @param class-string $className
     * @return Group[]
     */
    public function find(string $className, string ...$methodNames): array
    {
        $foundGroups = [
            $this->findInClasses($className),
            $this->findInMethodCalls($className, '__construct'),
        ];
        foreach ($methodNames as $methodName) {
            $foundGroups[] = $this->findInMethodCalls($className, $methodName);
        }
        return ItemCombinator::merge(...$foundGroups);
    }

    /**
     * @return Group[]
     */
    private function findInClasses(string $className): array
    {
        $classReflection = $this->reflectionProvider->getClass($className);

        $assignedGroups = $this->assignedGroups[$className][''] ?? [];
        foreach ($classReflection->getParentClassesNames() as $parentClass) {
            $assignedGroups = ItemCombinator::merge($this->assignedGroups[$parentClass][''] ?? [], $assignedGroups);
        }
        return $assignedGroups;
    }

    /**
     * @param class-string $className
     * @param ?class-string $currentClassName
     * @return Group[]
     */
    private function findInMethodCalls(string $className, string $methodName, ?string $currentClassName = null): array
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled, ?string $currentClassName = null) {
            /** @var array<Group[]> $fromCalled */
            /** @var Group[] $groups */
            $groups = $this->assignedGroups[$declaringClass][$methodName] ?? [];
            return ItemCombinator::merge($groups, ...$fromCalled);
        };
        return $this->methodCallFinder->traverseCalled($callback, $className, $methodName, $currentClassName);
    }
}
