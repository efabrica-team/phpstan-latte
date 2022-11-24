<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedMethodCall;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\MissingMethodFromReflectionException;

final class MethodCallCollector implements Collector
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @param CallLike $node
     */
    public function processNode(Node $node, Scope $scope): ?CollectedMethodCall
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }
        $actualClassName = $classReflection->getName();

        if (!$node instanceof MethodCall && !$node instanceof StaticCall) {
            return null;
        }

        $calledClassName = null;
        if ($node instanceof StaticCall) {
            $calledClassName = $this->nameResolver->resolve($node->class);
            if ($calledClassName === 'parent') {
                $classReflection = $classReflection->getParentClass();
                $calledClassName = $classReflection->getName();
            }
        } elseif ($node->var instanceof Variable && is_string($node->var->name) && $node->var->name === 'this') {
            $calledClassName = $classReflection->getName();
        }

        $calledMethodName = $this->nameResolver->resolve($node->name);

        if ($calledClassName === null || $calledMethodName === null) {
            return null;
        }

        try {
            $methodReflection = $classReflection->getMethod($calledMethodName, $scope);
        } catch (MissingMethodFromReflectionException $e) {
            return null;
        }
        $calledClassName = $methodReflection->getDeclaringClass()->getName();

        // Do not find template variables in nette classes
        if (in_array($calledClassName, [
            'Nette\Application\UI\Presenter',
            'Nette\Application\UI\Control',
            'Nette\Application\UI\Component',
            'Nette\ComponentModel\Container',
            'Latte\Runtime\Template',
        ], true)) {
            return null;
        }

        return new CollectedMethodCall(
            $actualClassName,
            $scope->getFunctionName(),
            $calledClassName,
            $calledMethodName
        );
    }
}
