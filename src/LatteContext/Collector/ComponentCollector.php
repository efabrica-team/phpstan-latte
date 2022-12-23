<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedComponent;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Efabrica\PHPStanLatte\Template\Component;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;

/**
 * @extends AbstractLatteContextCollector<Node, CollectedComponent>
 */
final class ComponentCollector extends AbstractLatteContextCollector
{
    private ValueResolver $valueResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        ValueResolver $valueResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->valueResolver = $valueResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @phpstan-return null|CollectedComponent[]
     */
    public function collectData(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        if ($node instanceof Return_) {
            return $this->findCreateComponent($node, $scope, $classReflection);
        }

        if ($node instanceof MethodCall) {
            return $this->findAddComponent($node, $scope, $classReflection);
        }

        if ($node instanceof Assign) {
            return $this->findAssignToThis($node, $scope, $classReflection);
        }

        // TODO add other components registrations - traits

        return null;
    }

    /**
     * @phpstan-return CollectedComponent[]
     */
    private function findCreateComponent(Return_ $node, Scope $scope, ClassReflection $classReflection): ?array
    {
        // TODO check if actual class is control / presenter

        $methodName = $scope->getFunctionName();
        if ($methodName === null || !str_starts_with($methodName, 'createComponent') || $methodName === 'createComponent') {
            return null;
        }

        $methodReflection = $classReflection->getNativeMethod($methodName);
        $parametersAcceptor = $methodReflection->getVariants()[0] ?? null;
        if ($parametersAcceptor === null) {
            return null;
        }

        if ($this->lattePhpDocResolver->resolveForMethod($classReflection->getName(), $methodName)->isIgnored()) {
            return null;
        }

        $componentType = $parametersAcceptor->getReturnType();
        if ($componentType instanceof MixedType && $node->expr !== null) {
            $componentType = $scope->getType($node->expr);
        }

        $componentName = lcfirst(str_replace('createComponent', '', $methodName));
        return [new CollectedComponent(
            $classReflection->getName(),
            '',
            new Component($componentName, $componentType)
        )];
    }

    /**
     * @phpstan-return CollectedComponent[]
     */
    private function findAddComponent(MethodCall $node, Scope $scope, ClassReflection $classReflection): ?array
    {
        // TODO check if caller class is control / presenter

        if ($this->nameResolver->resolve($node) !== 'addComponent') {
            return null;
        }

        if (count($node->getArgs()) < 2) {
            return null;
        }

        $componentArg = $node->getArgs()[0]->value;
        $componentNameArg = $node->getArgs()[1]->value;
        $componentArgType = $scope->getType($componentArg);

        $names = $this->valueResolver->resolve($componentNameArg, $scope);
        if ($names === null) {
            return null;
        }

        if ($this->lattePhpDocResolver->resolveForNode($node, $scope)->isIgnored()) {
            return null;
        }

        $components = [];
        foreach ($names as $name) {
            if (!is_string($name)) {
                continue;
            }
            $components[] = new CollectedComponent(
                $classReflection->getName(),
                $scope->getFunctionName() ?: '',
                new Component($name, $componentArgType)
            );
        }
        return $components;
    }

    /**
     * @phpstan-return CollectedComponent[]
     */
    private function findAssignToThis(Assign $node, Scope $scope, ClassReflection $classReflection): ?array
    {
        // TODO check if actual class is control / presenter

        if (!$node->var instanceof ArrayDimFetch) {
            return null;
        }
        if (!$node->var->var instanceof Variable) {
            return null;
        }
        if ($node->var->var->name !== 'this') {
            return null;
        }

        $exprType = $scope->getType($node->expr);
        if (!($exprType instanceof ObjectType && $exprType->isInstanceOf('Nette\ComponentModel\IComponent')->yes())) {
            return null;
        }
        if (!$node->var->dim instanceof Expr) {
            return null;
        }

        if ($this->lattePhpDocResolver->resolveForNode($node, $scope)->isIgnored()) {
            return null;
        }

        $names = $this->valueResolver->resolve($node->var->dim, $scope);
        if ($names === null) {
            return null;
        }

        $components = [];
        foreach ($names as $name) {
            if (!is_string($name)) {
                continue;
            }
            $components[] = new CollectedComponent(
                $classReflection->getName(),
                $scope->getFunctionName() ?: '',
                new Component($name, $exprType)
            );
        }
        return $components;
    }
}
