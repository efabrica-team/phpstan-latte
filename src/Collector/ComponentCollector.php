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
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;

/**
 * @phpstan-import-type CollectedComponentArray from CollectedComponent
 * @implements Collector<Node, ?CollectedComponentArray>
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

    /**
     * @phpstan-return null|CollectedComponentArray
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
     * @phpstan-return null|CollectedComponentArray
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
        return (new CollectedComponent(
            $classReflection->getName(),
            '',
            new Component($componentName, $parametersAcceptor->getReturnType())
        ))->toArray();
    }

    /**
     * @phpstan-return null|CollectedComponentArray
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

        $componentName = $this->valueResolver->resolve($componentNameArg, $scope->getFile());
        if (!is_string($componentName)) {
            return null;
        }
        $componentArgType = $scope->getType($componentArg);

        return (new CollectedComponent(
            $classReflection->getName(),
            $scope->getFunctionName() ?: '',
            new Component($componentName, $componentArgType)
        ))->toArray();
    }

    /**
     * @phpstan-return null|CollectedComponentArray
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

        $componentName = $this->valueResolver->resolve($node->var->dim);
        if (!is_string($componentName)) {
            return null;
        }

        return (new CollectedComponent(
            $classReflection->getName(),
            $scope->getFunctionName() ?: '',
            new Component($componentName, $exprType)
        ))->toArray();
    }
}
