<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\CallResolver;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;

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
            if ($node->class instanceof Variable) {
                return null;
            }
            $calledClassName = $this->nameResolver->resolve($node->class);
            if ($calledClassName === 'parent' && $this->nameResolver->resolve($node) !== $scope->getFunctionName()) {
                $classReflection = $classReflection->getParentClass();
                if ($classReflection === null) {
                    return null;
                }
                return $classReflection->getName();
            } elseif ($calledClassName === 'self') {
                return $classReflection->getName();
            }
            return $calledClassName;
        }

        if (!$node instanceof MethodCall) {
            return null;
        }

        if ($node->var instanceof Variable && is_string($node->var->name) && $node->var->name === 'this') {
            return 'this';
        } else {
            if ($node->var === null) {
                return null;
            }
            $callerType = $scope->getType($node->var);
            $callerClassNames = $callerType->getObjectClassNames();
            return $callerClassNames[0] ?? null;
        }
    }
}
