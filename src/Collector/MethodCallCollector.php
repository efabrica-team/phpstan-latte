<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedMethodCall;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\CallResolver\CalledClassResolver;
use Efabrica\PHPStanLatte\Resolver\CallResolver\TerminatingCallResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @phpstan-import-type CollectedMethodCallArray from CollectedMethodCall
 * @extends AbstractCollector<CallLike, CollectedMethodCall, CollectedMethodCallArray>
 */
final class MethodCallCollector extends AbstractCollector
{
    private CalledClassResolver $calledClassResolver;

    private TerminatingCallResolver $terminatingCallResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        TypeSerializer $typeSerializer,
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        Parser $parser,
        NodeScopeResolver $nodeScopeResolver,
        CalledClassResolver $calledClassResolver,
        TerminatingCallResolver $terminatingCallResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($typeSerializer, $nameResolver, $reflectionProvider, $parser, $nodeScopeResolver);
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
     * @phpstan-return null|CollectedMethodCallArray[]
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
            return $this->collectItem(new CollectedMethodCall(
                $actualClassName,
                $functionName,
                $calledClassName ?? '',
                $calledMethodName ?? '',
                CollectedMethodCall::TERMINATING_CALL
            ));
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
        return $this->collectItem(new CollectedMethodCall(
            $actualClassName,
            $functionName,
            $declaringClassName,
            $calledMethodName
        ));
    }
}
