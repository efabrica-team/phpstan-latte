<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\CallResolver;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\NeverType;
use PHPStan\Type\TypeUtils;

final class TerminatingCallResolver
{
    /** @var array<string, string[]> */
    private array $earlyTerminatingMethodCalls;

    /** @var array<int, string> */
    private array $earlyTerminatingFunctionCalls;

    /** @var array<string, true> */
    private array $earlyTerminatingMethodNames;

    private ReflectionProvider $reflectionProvider;

    /**
     * @param array<string, string[]> $earlyTerminatingMethodCalls className(string) => methods(string[])
     * @param array<int, string> $earlyTerminatingFunctionCalls
     */
    public function __construct(
        array $earlyTerminatingMethodCalls,
        array $earlyTerminatingFunctionCalls,
        ReflectionProvider $reflectionProvider
    ) {
        $this->earlyTerminatingMethodCalls = $earlyTerminatingMethodCalls;
        $this->earlyTerminatingFunctionCalls = $earlyTerminatingFunctionCalls;
        $earlyTerminatingMethodNames = [];
        foreach ($this->earlyTerminatingMethodCalls as $methodNames) {
            foreach ($methodNames as $methodName) {
                $earlyTerminatingMethodNames[strtolower($methodName)] = true;
            }
        }
        $this->earlyTerminatingMethodNames = $earlyTerminatingMethodNames;
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * Copy of method from phpstan: PHPStan\Analyser\NodeScopeResolver::findEarlyTerminatingExpr
     */
    public function isTerminatingCallNode(Expr $expr, Scope $scope): bool
    {
        if (($expr instanceof MethodCall || $expr instanceof StaticCall) && $expr->name instanceof Identifier) {
            if (array_key_exists($expr->name->toLowerString(), $this->earlyTerminatingMethodNames)) {
                if ($expr instanceof MethodCall) {
                    $methodCalledOnType = $scope->getType($expr->var);
                } else {
                    if ($expr->class instanceof Name) {
                        $methodCalledOnType = $scope->resolveTypeByName($expr->class);
                    } else {
                        $methodCalledOnType = $scope->getType($expr->class);
                    }
                }
                $directClassNames = TypeUtils::getDirectClassNames($methodCalledOnType);
                foreach ($directClassNames as $referencedClass) {
                    if (!$this->reflectionProvider->hasClass($referencedClass)) {
                        continue;
                    }
                    $classReflection = $this->reflectionProvider->getClass($referencedClass);
                    foreach (array_merge([$referencedClass], $classReflection->getParentClassesNames(), $classReflection->getNativeReflection()->getInterfaceNames()) as $className) {
                        if (!isset($this->earlyTerminatingMethodCalls[$className])) {
                            continue;
                        }
                        if (in_array((string) $expr->name, $this->earlyTerminatingMethodCalls[$className], true)) {
                            return true;
                        }
                    }
                }
            }
        }
        if ($expr instanceof FuncCall && $expr->name instanceof Name) {
            if (in_array((string) $expr->name, $this->earlyTerminatingFunctionCalls, true)) {
                return true;
            }
        }
        if ($expr instanceof Exit_ || $expr instanceof Throw_) {
            return true;
        }
        $exprType = $scope->getType($expr);
        if ($exprType instanceof NeverType && $exprType->isExplicit()) {
            return true;
        }
        return false;
    }
}
