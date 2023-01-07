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
            $this->variableFinder->find($className, 'startup'),
            $this->variableFinder->find($className, 'beforeRender'),
            [
                new Variable('presenter', $presenterType),
                new Variable('control', $presenterType),
            ]
        );
    }

    protected function getClassGlobalComponents(ReflectionClass $reflectionClass): array
    {
        $className = $reflectionClass->getName();
        return array_merge(
            $this->componentFinder->find($className, ''),
            $this->componentFinder->find($className, 'startup'),
            $this->componentFinder->find($className, 'beforeRender')
        );
    }

    protected function getClassGlobalForms(ReflectionClass $reflectionClass): array
    {
        $className = $reflectionClass->getName();
        return array_merge(
            $this->formFinder->find($className, ''),
            $this->formFinder->find($className, 'startup'),
            $this->formFinder->find($className, 'beforeRender')
        );
    }

    protected function getClassGlobalFilters(ReflectionClass $reflectionClass): array
    {
        $className = $reflectionClass->getName();
        return array_merge(
            $this->filterFinder->find($className, ''),
            $this->filterFinder->find($className, 'startup'),
            $this->filterFinder->find($className, 'beforeRender')
        );
    }
}
