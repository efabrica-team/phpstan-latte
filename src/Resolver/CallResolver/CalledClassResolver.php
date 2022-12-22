<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\CallResolver;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

final class CalledClassResolver
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function resolve(Node $node, Scope $scope): ?string
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        if ($node instanceof StaticCall) {
            $calledClassName = $this->nameResolver->resolve($node->class);
            if ($calledClassName === 'parent') {
                $classReflection = $classReflection->getParentClass();
                if ($classReflection === null) {
                    return null;
                }
                return $classReflection->getName();
            } elseif ($calledClassName === 'self' || $calledClassName === 'static') {
                return $classReflection->getName();
            }
            return $calledClassName;
        }

        if (!$node instanceof MethodCall) {
            return null;
        }

        if ($node->var instanceof Variable && is_string($node->var->name) && $node->var->name === 'this') {
            return $classReflection->getName();
        } else {
            if ($node->var === null) {
                return null;
            }
            $callerType = $scope->getType($node->var);
            return $callerType instanceof ObjectType ? $callerType->describe(VerbosityLevel::typeOnly()) : null;
        }
    }

    public function resolveDeclaring(Node $node, Scope $scope): ?string
    {
        if (!$node instanceof MethodCall && !$node instanceof StaticCall) {
            return null;
        }

        $calledClassName = $this->resolve($node, $scope);
        $calledMethodName = $this->nameResolver->resolve($node);

        if ($calledClassName === null || $calledMethodName === null || $calledMethodName === '') {
            return null;
        }

        try {
            $reflectionClass = (new BetterReflection())->reflector()->reflectClass($calledClassName);
        } catch (IdentifierNotFound $e) {
            return null;
        }

        $reflectionMethod = $reflectionClass->getMethod($calledMethodName);
        if ($reflectionMethod === null) {
            return null;
        }

        return $reflectionMethod->getDeclaringClass()->getName();
    }
}
