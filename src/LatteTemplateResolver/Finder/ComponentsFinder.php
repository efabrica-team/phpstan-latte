<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver\Finder;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Efabrica\PHPStanLatte\Template\Component;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use ReflectionClass;

final class ComponentsFinder
{
    private NameResolver $nameResolver;

    private ValueResolver $valueResolver;

    public function __construct(NameResolver $nameResolver, ValueResolver $valueResolver)
    {
        $this->nameResolver = $nameResolver;
        $this->valueResolver = $valueResolver;
    }

    /**
     * @return Component[]
     */
    public function findForClass(Class_ $class, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        /** @var class-string $className */
        $className = (string)$class->namespacedName;
        $reflectionClass = new ReflectionClass($className);
        $reflectionMethods = $reflectionClass->getMethods();

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

    /**
     * @return Component[]
     */
    public function findForMethod(ClassMethod $classMethod, Scope $scope): array
    {
        $nodeTraverser = new NodeTraverser();

        $componentsNodeVisitor = new class($scope, $this->nameResolver, $this->valueResolver) extends NodeVisitorAbstract
        {
            private Scope $scope;

            private NameResolver $nameResolver;

            private ValueResolver $valueResolver;

            /** @var Component[] */
            private array $components = [];

            public function __construct(Scope $scope, NameResolver $nameResolver, ValueResolver $valueResolver)
            {
                $this->scope = $scope;
                $this->nameResolver = $nameResolver;
                $this->valueResolver = $valueResolver;
            }

            public function enterNode(Node $node): ?Node
            {
                $this->findAddComponent($node);
                return null;
            }

            /**
             * @return Component[]
             */
            public function getComponents(): array
            {
                return $this->components;
            }

            private function findAddComponent(Node $node): void
            {
                if (!$node instanceof MethodCall) {
                    return;
                }

                if ($this->nameResolver->resolve($node->name) !== 'addComponent') {
                    return;
                }

                if (count($node->getArgs()) < 2) {
                    return;
                }

                $componentArg = $node->getArgs()[0]->value;
                $componentNameArg = $node->getArgs()[1]->value;

                $componentName = $this->valueResolver->resolve($componentNameArg, $this->scope);
                if (!is_string($componentName)) {
                    return;
                }
                $componentArgType = $this->scope->getType($componentArg);

                $this->components[] = new Component($componentName, $componentArgType);
            }
        };
        $nodeTraverser->addVisitor($componentsNodeVisitor);
        $nodeTraverser->traverse((array)$classMethod->stmts);

        return $componentsNodeVisitor->getComponents();
    }
}
