<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;
use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\LatteContext\Resolver\ClassLatteContextResolver;
use Efabrica\PHPStanLatte\LatteContext\Resolver\LatteContextResolverInterface;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Filter;
use Efabrica\PHPStanLatte\Template\Form\Form;
use Efabrica\PHPStanLatte\Template\TemplateContext;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Node\InClassNode;
use PHPStan\Type\ObjectType;

abstract class AbstractClassTemplateResolver implements NodeLatteTemplateResolverInterface
{
    private const PARAM_CLASS_NAME = 'className';

    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(LattePhpDocResolver $lattePhpDocResolver)
    {
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function collect(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        if (!$node instanceof InClassNode) {
            return [];
        }

        $class = $node->getOriginalNode();
        if (!$class instanceof Class_) {
            return [];
        }

        $className = (string)$class->namespacedName;
        if (!$className) {
            return [];
        }

        $objectType = new ObjectType($className);

        foreach ($this->getIgnoredClasses() as $ignoredClass) {
            if ($objectType->isInstanceOf($ignoredClass)->yes()) {
                return [];
            }
        }

        foreach ($this->getSupportedClasses() as $supportedClass) {
            if ($objectType->isInstanceOf($supportedClass)->yes()) {
                return [new CollectedResolvedNode(static::class, $scope->getFile(), [self::PARAM_CLASS_NAME => $className])];
            }
        }

        return [];
    }

    /**
     * @return LatteTemplateResolverResult
     */
    public function resolve(CollectedResolvedNode $resolvedNode, LatteContext $latteContext): LatteTemplateResolverResult
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

    protected function getClassContextResolver(ReflectionClass $reflectionClass, LatteContext $latteContext): LatteContextResolverInterface
    {
        return new ClassLatteContextResolver($reflectionClass, $latteContext);
    }

    /**
     * @return Variable[]
     */
    protected function getClassGlobalVariables(ReflectionClass $reflectionClass, LatteContext $latteContext): array
    {
        return $this->getClassContextResolver($reflectionClass, $latteContext)->getVariables();
    }

    /**
     * @return Component[]
     */
    protected function getClassGlobalComponents(ReflectionClass $reflectionClass, LatteContext $latteContext): array
    {
        return $this->getClassContextResolver($reflectionClass, $latteContext)->getComponents();
    }

    /**
     * @return Form[]
     */
    protected function getClassGlobalForms(ReflectionClass $reflectionClass, LatteContext $latteContext): array
    {
        return $this->getClassContextResolver($reflectionClass, $latteContext)->getForms();
    }

    /**
     * @return Filter[]
     */
    protected function getClassGlobalFilters(ReflectionClass $reflectionClass, LatteContext $latteContext): array
    {
        return $this->getClassContextResolver($reflectionClass, $latteContext)->getFilters();
    }

    protected function getClassGlobalTemplateContext(ReflectionClass $reflectionClass, LatteContext $latteContext): TemplateContext
    {
        return new TemplateContext(
            $this->getClassGlobalVariables($reflectionClass, $latteContext),
            $this->getClassGlobalComponents($reflectionClass, $latteContext),
            $this->getClassGlobalForms($reflectionClass, $latteContext),
            $this->getClassGlobalFilters($reflectionClass, $latteContext)
        );
    }

    abstract protected function getClassResult(ReflectionClass $resolveClass, LatteContext $latteContext): LatteTemplateResolverResult;
}
