<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedComponent;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Efabrica\PHPStanLatte\Template\Component;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ClassReflection;

/**
 * @implements Collector<Node, ?CollectedComponent>
 */
final class ComponentCollector implements Collector
{
    private NameResolver $nameResolver;

    private ValueResolver $valueResolver;

    public function __construct(NameResolver $nameResolver, ValueResolver $valueResolver)
    {
        $this->nameResolver = $nameResolver;
        $this->valueResolver = $valueResolver;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): ?CollectedComponent
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        if ($node instanceof ClassMethod) {
            return $this->findCreateComponent($node, $classReflection);
        }

        if ($node instanceof MethodCall) {
            return $this->findAddComponent($node, $scope, $classReflection);
        }

        // TODO add other components registrations - call $this->>addComponent() and also calls on Control $this['something'] = new SomeSubcomponent()

        return null;
    }

    private function findCreateComponent(ClassMethod $node, ClassReflection $classReflection): ?CollectedComponent
    {
        $methodName = $this->nameResolver->resolve($node->name);
        if ($methodName === null || !str_starts_with($methodName, 'createComponent') || $methodName === 'createComponent') {
            return null;
        }

        $methodReflection = $classReflection->getNativeMethod($methodName);
        $parametersAcceptor = $methodReflection->getVariants()[0] ?? null;
        if ($parametersAcceptor === null) {
            return null;
        }

        $componentName = lcfirst(str_replace('createComponent', '', $methodName));
        return new CollectedComponent(
            $classReflection->getName(),
            '',
            new Component($componentName, $parametersAcceptor->getReturnType())
        );
    }

    private function findAddComponent(MethodCall $node, Scope $scope, ClassReflection $classReflection): ?CollectedComponent
    {
        if ($this->nameResolver->resolve($node->name) !== 'addComponent') {
            return null;
        }

        if (count($node->getArgs()) < 2) {
            return null;
        }

        $componentArg = $node->getArgs()[0]->value;
        $componentNameArg = $node->getArgs()[1]->value;

        $componentName = $this->valueResolver->resolve($componentNameArg, $scope->getFile());
        if (!is_string($componentName)) {
            return null;
        }
        $componentArgType = $scope->getType($componentArg);

        return new CollectedComponent(
            $classReflection->getName(),
            $scope->getFunctionName() ?: '',
            new Component($componentName, $componentArgType)
        );
    }
}
