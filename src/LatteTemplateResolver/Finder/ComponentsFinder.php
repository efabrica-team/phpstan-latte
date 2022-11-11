<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver\Finder;

use Efabrica\PHPStanLatte\Template\Component;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use ReflectionClass;

final class ComponentsFinder
{
    /**
     * @return Component[]
     */
    public function find(Class_ $class, Scope $scope): array
    {
        $className = (string)$class->namespacedName;
        $reflectionClass = new ReflectionClass($className);
        $reflectionMethods = $reflectionClass->getMethods();

        $classReflection = $scope->getClassReflection();
        $components = [];
        foreach ($reflectionMethods as $reflectionMethod) {
            $methodName = $reflectionMethod->name;

            if (!str_starts_with($methodName, 'createComponent') || $methodName === 'createComponent') {
                continue;
            }

            $methodReflection = $classReflection->getNativeMethod($methodName);
            $parametersAcceptor = $methodReflection->getVariants()[0] ?? null;
            if ($parametersAcceptor === null) {
                continue;
            }

            $componentName = lcfirst(str_replace('createComponent', '', $methodName));
            $components[] = new Component($componentName, $parametersAcceptor->getReturnType());
        }

        return $components;
    }
}
