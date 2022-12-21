<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedMethodCall;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\CallResolver\CalledClassResolver;
use Efabrica\PHPStanLatte\Resolver\CallResolver\TerminatingCallResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends AbstractLatteContextCollector<CallLike, CollectedMethodCall>
 */
final class MethodCallCollector extends AbstractLatteContextCollector
{
    private CalledClassResolver $calledClassResolver;

    private TerminatingCallResolver $terminatingCallResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        CalledClassResolver $calledClassResolver,
        TerminatingCallResolver $terminatingCallResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->calledClassResolver = $calledClassResolver;
        $this->terminatingCallResolver = $terminatingCallResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @param CallLike $node
     * @phpstan-return null|CollectedMethodCall[]
     */
    public function collectData(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $functionName = $scope->getFunctionName();
        if ($functionName === null) {
            return null;
        }

        if ($this->lattePhpDocResolver->resolveForNode($node, $scope)->isIgnored()) {
            return null;
        }

        $actualClassName = $classReflection->getName();
        $calledClassName = $this->calledClassResolver->resolve($node, $scope);
        $calledMethodName = $this->nameResolver->resolve($node);

        if ($this->terminatingCallResolver->isTerminatingCallNode($node, $scope)) {
            return [new CollectedMethodCall(
                $actualClassName,
                $functionName,
                $calledClassName ?? '',
                $calledMethodName ?? '',
                CollectedMethodCall::TERMINATING_CALL
            )];
        }

        if ($calledClassName === null || $calledMethodName === null || $calledMethodName === '') {
            return null;
        }

        if ($this->lattePhpDocResolver->resolveForMethod($calledClassName, $calledMethodName)->isIgnored()) {
            return null;
        }

        if (!$node instanceof MethodCall && !$node instanceof StaticCall) {
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

        $declaringClassName = $reflectionMethod->getDeclaringClass()->getName();
        return [new CollectedMethodCall(
            $actualClassName,
            $functionName,
            $declaringClassName,
            $calledMethodName
        )];
    }
}
