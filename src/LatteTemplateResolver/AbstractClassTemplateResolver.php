<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedForm;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedNode;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Node\CollectedDataNode;
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
    protected function getResult(CollectedResolvedNode $resolvedNode, CollectedDataNode $collectedDataNode): LatteTemplateResolverResult
    {
        $className = $resolvedNode->getParam(self::PARAM_CLASS_NAME);
        $reflectionClass = (new BetterReflection())->reflector()->reflectClass($className);

        $fileName = $reflectionClass->getFileName();
        if ($fileName === null) {
            return new LatteTemplateResolverResult();
        }

        return $this->getClassResult($reflectionClass, $collectedDataNode);
    }

    /**
     * @return ReflectionMethod[]
     */
    protected function getMethodsMatching(ReflectionClass $reflectionClass, string $pattern): array
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
     * @return CollectedForm[]
     */
    abstract protected function getClassGlobalForms(ReflectionClass $reflectionClass): array;

    abstract protected function getClassResult(ReflectionClass $resolveClass, CollectedDataNode $collectedDataNode): LatteTemplateResolverResult;
}
