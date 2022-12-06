<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedComponent;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Efabrica\PHPStanLatte\Template\Component;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;

/**
 * @phpstan-import-type CollectedComponentArray from CollectedComponent
 * @extends AbstractCollector<Node, CollectedComponent, CollectedComponentArray>
 */
final class ComponentCollector extends AbstractCollector
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

    /**
     * @phpstan-return null|CollectedComponentArray[]
     */
    public function processNode(Node $node, Scope $scope): ?array
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

        if ($node instanceof Assign) {
            return $this->findAssignToThis($node, $scope, $classReflection);
        }

        // TODO add other components registrations - traits

        return null;
    }

    /**
     * @phpstan-return CollectedComponentArray[]
     */
    private function findCreateComponent(ClassMethod $node, ClassReflection $classReflection): ?array
    {
        // TODO check if actual class is control / presenter

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
        return $this->collectItem(new CollectedComponent(
            $classReflection->getName(),
            '',
            new Component($componentName, $parametersAcceptor->getReturnType())
        ));
    }

    /**
     * @phpstan-return CollectedComponentArray[]
     */
    private function findAddComponent(MethodCall $node, Scope $scope, ClassReflection $classReflection): ?array
    {
        // TODO check if caller class is control / presenter

        if ($this->nameResolver->resolve($node->name) !== 'addComponent') {
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
        return $this->collectItems($components);
    }

    /**
     * @phpstan-return CollectedComponentArray[]
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
        return $this->collectItems($components);
    }
}
