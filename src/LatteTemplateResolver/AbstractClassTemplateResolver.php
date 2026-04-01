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
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use ReflectionException;
use function dirname;
use function preg_match;

abstract class AbstractClassTemplateResolver implements NodeLatteTemplateResolverInterface
{
    private const PARAM_CLASS_NAME = 'className';

    private LattePhpDocResolver $lattePhpDocResolver;

    private ReflectionProvider $reflectionProvider;

    public function __construct(LattePhpDocResolver $lattePhpDocResolver, ReflectionProvider $reflectionProvider)
    {
        $this->lattePhpDocResolver = $lattePhpDocResolver;
        $this->reflectionProvider = $reflectionProvider;
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

        $supported = false;
        foreach ($this->getSupportedClasses() as $supportedClass) {
            if ($supportedClass === 'object' || $objectType->isInstanceOf($supportedClass)->yes()) {
                $supported = true;
            }
        }

        if (!$supported) {
            return [];
        }

        if (preg_match($this->getClassNamePattern() . 'i', $className) !== 1) {
            return [];
        }

        return [new CollectedResolvedNode(static::class, $scope->getFile(), [self::PARAM_CLASS_NAME => $className])];
    }

    public function resolve(CollectedResolvedNode $resolvedNode, LatteContext $latteContext): LatteTemplateResolverResult
    {
        $className = $resolvedNode->getParam(self::PARAM_CLASS_NAME);
        $classReflection = $this->reflectionProvider->getClass($className);

        $fileName = $classReflection->getFileName();
        if ($fileName === null) {
            return new LatteTemplateResolverResult();
        }

        return $this->getClassResult($classReflection, $latteContext);
    }

    /**
     * @return MethodReflection[]
     */
    protected function getMethodsMatching(ClassReflection $classReflection, string $pattern): array
    {
        $methods = [];
        foreach ($this->getMethodsMatchingIncludingIgnored($classReflection, $pattern) as $method) {
            if (!$this->lattePhpDocResolver->resolveForMethod($classReflection->getName(), $method->getName())->isIgnored()) {
                $methods[] = $method;
            }
        }
        return $methods;
    }

    /**
     * @return MethodReflection[]
     */
    protected function getMethodsMatchingIncludingIgnored(ClassReflection $classReflection, string $pattern): array
    {
        $methods = [];
        foreach ($classReflection->getNativeReflection()->getMethods() as $nativeMethod) {
            if (preg_match($pattern . 'i', $nativeMethod->getName()) === 1) {
                $methods[] = $classReflection->getNativeMethod($nativeMethod->getName());
            }
        }
        return $methods;
    }

    protected function getMethodStartLine(ClassReflection $classReflection, string $methodName): int
    {
        try {
            $line = $classReflection->getNativeReflection()->getMethod($methodName)->getStartLine();
            return $line !== false ? $line : -1;
        } catch (ReflectionException $e) {
            return -1;
        }
    }

    protected function getClassDir(ClassReflection $classReflection): ?string
    {
        $fileName = $classReflection->getFileName();
        if ($fileName === null) {
            return null;
        }
        return dirname($fileName);
    }

    /**
     * @return array<class-string|"object">
     */
    abstract protected function getSupportedClasses(): array;

    /**
     * @return class-string[]
     */
    protected function getIgnoredClasses(): array
    {
        return [];
    }

    protected function getClassContextResolver(ClassReflection $classReflection, LatteContext $latteContext): LatteContextResolverInterface
    {
        return new ClassLatteContextResolver($classReflection, $latteContext);
    }

    /**
     * @return Variable[]
     */
    protected function getClassGlobalVariables(ClassReflection $classReflection, LatteContext $latteContext): array
    {
        return $this->getClassContextResolver($classReflection, $latteContext)->getVariables();
    }

    /**
     * @return Component[]
     */
    protected function getClassGlobalComponents(ClassReflection $classReflection, LatteContext $latteContext): array
    {
        return $this->getClassContextResolver($classReflection, $latteContext)->getComponents();
    }

    /**
     * @return Form[]
     */
    protected function getClassGlobalForms(ClassReflection $classReflection, LatteContext $latteContext): array
    {
        return $this->getClassContextResolver($classReflection, $latteContext)->getForms();
    }

    /**
     * @return Filter[]
     */
    protected function getClassGlobalFilters(ClassReflection $classReflection, LatteContext $latteContext): array
    {
        return $this->getClassContextResolver($classReflection, $latteContext)->getFilters();
    }

    protected function getClassGlobalTemplateContext(ClassReflection $classReflection, LatteContext $latteContext): TemplateContext
    {
        return new TemplateContext(
            $this->getClassGlobalVariables($classReflection, $latteContext),
            $this->getClassGlobalComponents($classReflection, $latteContext),
            $this->getClassGlobalForms($classReflection, $latteContext),
            $this->getClassGlobalFilters($classReflection, $latteContext)
        );
    }

    protected function getClassNamePattern(): string
    {
        return '/.*/';
    }

    abstract protected function getClassResult(ClassReflection $classReflection, LatteContext $latteContext): LatteTemplateResolverResult;
}
