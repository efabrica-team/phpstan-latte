<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\CallResolver;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;

final class TerminatingCallResolver
{
    /**
     * @var array<string, string[]>
     */
    private array $earlyTerminatingMethodCalls;

    private NameResolver $nameResolver;

    private CalledClassResolver $calledClassResolver;

    /**
     * @param array<string, string[]> $earlyTerminatingMethodCalls
     */
    public function __construct(array $earlyTerminatingMethodCalls, NameResolver $nameResolver, CalledClassResolver $calledClassResolver)
    {
        $this->earlyTerminatingMethodCalls = $earlyTerminatingMethodCalls;
        $this->nameResolver = $nameResolver;
        $this->calledClassResolver = $calledClassResolver;
    }

    public function isTerminatingCallNode(Node $node, Scope $scope): bool
    {
        if (!$node instanceof MethodCall && !$node instanceof StaticCall) {
            return false;
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return false;
        }

        $calledClassName = $this->calledClassResolver->resolve($node, $scope);
        if (in_array($calledClassName, ['this', 'self', 'static', 'parent'], true)) {
            $calledClassName = $classReflection->getName();
        } elseif ($calledClassName === 'parent' && $classReflection->getParentClass()) {
            $calledClassName = $classReflection->getParentClass()->getName();
        }

        $calledMethodName = $this->nameResolver->resolve($node->name);
        if ($calledClassName === null || $calledMethodName === null || $calledMethodName === '') {
            return false;
        }
        return $this->isTerminatingMethodCall($calledClassName, $calledMethodName);
    }

    public function isTerminatingMethodCall(string $calledClassName, string $calledMethodName): bool
    {
        $objectType = new ObjectType($calledClassName);

        foreach ($this->earlyTerminatingMethodCalls as $class => $methods) {
            foreach ($methods as $method) {
                if ($objectType->isInstanceOf($class)->yes() && $calledMethodName === $method) {
                    return true;
                }
            }
        }

        return false;
    }
}
