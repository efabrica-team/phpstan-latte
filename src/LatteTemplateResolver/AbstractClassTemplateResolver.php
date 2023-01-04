<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Filter;
use Efabrica\PHPStanLatte\Template\Form\Form;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Node\InClassNode;
use PHPStan\Type\ObjectType;

abstract class AbstractClassTemplateResolver extends AbstractTemplateResolver
{
    private const PARAM_CLASS_NAME = 'className';

    public function collect(Node $node, Scope $scope): ?CollectedResolvedNode
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        if (!$node instanceof InClassNode) {
            return null;
        }

        $class = $node->getOriginalNode();
        if (!$class instanceof Class_) {
            return null;
        }

        $className = (string)$class->namespacedName;
        if (!$className) {
            return null;
        }

        $objectType = new ObjectType($className);

        foreach ($this->getIgnoredClasses() as $ignoredClass) {
            if ($objectType->isInstanceOf($ignoredClass)->yes()) {
                return null;
            }
        }

        foreach ($this->getSupportedClasses() as $supportedClass) {
            if ($objectType->isInstanceOf($supportedClass)->yes()) {
                return new CollectedResolvedNode(static::class, [self::PARAM_CLASS_NAME => $className]);
            }
        }

        return null;
    }

    /**
     * @return LatteTemplateResolverResult
     */
    protected function getResult(CollectedResolvedNode $resolvedNode, LatteContextData $latteContext): LatteTemplateResolverResult
    {
        $className = $resolvedNode->getParam(self::PARAM_CLASS_NAME);
        $reflectionClass = (new BetterReflection())->reflector()->reflectClass($className);

        $fileName = $reflectionClass->getFileName();
        if ($fileName === null) {
            return new LatteTemplateResolverResult();
        }

        return $this->getClassResult($reflectionClass, $latteContext);
    }

    /**
     * @return ReflectionMethod[]
     */
    protected function getMethodsMatching(ReflectionClass $reflectionClass, string $pattern): array
    {
        $methods = [];
        foreach ($this->getMethodsMatchingIncludingIgnored($reflectionClass, $pattern) as $reflectionMethod) {
            if (!$this->lattePhpDocResolver->resolveForMethod($reflectionClass->getName(), $reflectionMethod->getName())->isIgnored()) {
                $methods[] = $reflectionMethod;
            }
        }
        return $methods;
    }

    /**
     * @return ReflectionMethod[]
     */
    protected function getMethodsMatchingIncludingIgnored(ReflectionClass $reflectionClass, string $pattern): array
    {
        $methods = [];
        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            if (preg_match($pattern . 'i', $reflectionMethod->getName()) === 1) {
                $methods[] = $reflectionMethod;
            }
        }
        return $methods;
    }

    protected function getClassDir(ReflectionClass $reflectionClass): ?string
    {
        $fileName = $reflectionClass->getFileName();
        if ($fileName === null) {
            return null;
        }
        return dirname($fileName);
    }

    /**
     * @return class-string[]
     */
    abstract protected function getSupportedClasses(): array;

    /**
     * @return class-string[]
     */
    protected function getIgnoredClasses(): array
    {
        return [];
    }

    /**
     * @return Variable[]
     */
    abstract protected function getClassGlobalVariables(ReflectionClass $reflectionClass): array;

    /**
     * @return Component[]
     */
    abstract protected function getClassGlobalComponents(ReflectionClass $reflectionClass): array;

    /**
     * @return Form[]
     */
    abstract protected function getClassGlobalForms(ReflectionClass $reflectionClass): array;

    /**
     * @return Filter[]
     */
    abstract protected function getClassGlobalFilters(ReflectionClass $reflectionClass): array;

    abstract protected function getClassResult(ReflectionClass $resolveClass, LatteContextData $latteContext): LatteTemplateResolverResult;
}
