<?php

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Type\ObjectType;

trait NetteApplicationUIControlGlobals
{
    protected function getClassGlobalVariables(ReflectionClass $reflectionClass): array
    {
        $controlType = new ObjectType($reflectionClass->getName());
        return [
            new Variable('presenter', $controlType),
            new Variable('control', $controlType),
        ];
    }

    protected function getClassGlobalComponents(ReflectionClass $reflectionClass): array
    {
        return $this->componentFinder->find($reflectionClass->getName(), '');
    }

    protected function getClassGlobalForms(ReflectionClass $reflectionClass): array
    {
        return $this->formFinder->find($reflectionClass->getName(), '');
    }

    protected function getClassGlobalFilters(ReflectionClass $reflectionClass): array
    {
        return $this->filterFinder->find($reflectionClass->getName(), '');
    }
}
