<?php

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Type\ObjectType;

trait NetteApplicationUIPresenterGlobals
{
    protected function getClassGlobalVariables(ReflectionClass $reflectionClass): array
    {
        $className = $reflectionClass->getName();
        $presenterType = new ObjectType($reflectionClass->getName());
        return array_merge(
            $this->variableFinder->find($className, 'startup', 'beforeRender'),
            [
                new Variable('presenter', $presenterType),
                new Variable('control', $presenterType),
            ]
        );
    }

    protected function getClassGlobalComponents(ReflectionClass $reflectionClass): array
    {
        $className = $reflectionClass->getName();
        return $this->componentFinder->find($className, 'startup', 'beforeRender');
    }

    protected function getClassGlobalForms(ReflectionClass $reflectionClass): array
    {
        $className = $reflectionClass->getName();
        return $this->formFinder->find($className, 'startup', 'beforeRender');
    }

    protected function getClassGlobalFilters(ReflectionClass $reflectionClass): array
    {
        $className = $reflectionClass->getName();
        return $this->filterFinder->find($className, 'startup', 'beforeRender');
    }
}
