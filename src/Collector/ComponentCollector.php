<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedComponent;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Template\Component;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

final class ComponentCollector implements Collector
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
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

        // TODO add other components registrations - call $this->>addComponent() and also calls on Control $this['something'] = new SomeSubcomponent()

        if (!$node instanceof ClassMethod) {
            return null;
        }

        $methodName = $this->nameResolver->resolve($node->name);
        if (!str_starts_with($methodName, 'createComponent') || $methodName === 'createComponent') {
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
}
