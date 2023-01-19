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
 * @extends AbstractLatteContextCollector<CollectedComponent>
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

    public function getNodeTypes(): array
    {
        return [
            Return_::class,
            MethodCall::class,
            Assign::class,
        ];
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

        $componentType = $parametersAcceptor->getReturnType();
        if ($componentType instanceof MixedType && $node->expr !== null) {
            $componentType = $scope->getType($node->expr);
        }

        $componentName = lcfirst(str_replace('createComponent', '', $methodName));

        $components = [CollectedComponent::build(null, $scope, $componentName, $componentType)];

        $lattePhpDoc = $this->lattePhpDocResolver->resolveForNode($node, $scope);
        if ($lattePhpDoc->isIgnored()) {
            return null;
        }
        if ($lattePhpDoc->hasComponents()) {
            $components = [];
            foreach ($lattePhpDoc->getComponents([$componentName]) as $name => $type) {
                $components[] = CollectedComponent::build(null, $scope, $name, $type);
            }
        }

        return $components;
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

        return $this->buildComponents($node, $scope, $classReflection, $node->getArgs()[1]->value, $node->getArgs()[0]->value);
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
        if (!$node->var->dim instanceof Expr) {
            return null;
        }

        return $this->buildComponents($node, $scope, $classReflection, $node->var->dim, $node->expr);
    }

    /**
     * @return ?CollectedComponent[] $components
     */
    private function buildComponents(Node $node, Scope $scope, ClassReflection $classReflection, Expr $componentNameArg, Expr $componentArg): ?array
    {
        $componentArgType = $scope->getType($componentArg);

        $names = $this->valueResolver->resolveStrings($componentNameArg, $scope) ?? [];

        $components = [];

        // TODO support union types
        if ($componentArgType instanceof ObjectType && $componentArgType->isInstanceOf('Nette\ComponentModel\IComponent')->yes()) {
            foreach ($names as $name) {
                $components[] = new CollectedComponent(
                    $classReflection->getName(),
                    $scope->getFunctionName() ?: '',
                    new Component($name, $componentArgType)
                );
            }
        }

        $lattePhpDoc = $this->lattePhpDocResolver->resolveForNode($node, $scope);
        if ($lattePhpDoc->isIgnored()) {
            return null;
        }
        if ($lattePhpDoc->hasComponents()) {
            $components = [];
            foreach ($lattePhpDoc->getComponents($names) as $name => $type) {
                $components[$name] = CollectedComponent::build($node, $scope, $name, $type);
            }
        }
        return count($components) > 0 ? array_values($components) : null;
    }
}
