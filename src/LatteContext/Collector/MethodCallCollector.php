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
use PhpParser\Node\Expr\Exit_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends AbstractLatteContextCollector<CollectedMethodCall>
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

    public function getNodeTypes(): array
    {
        return [
            CallLike::class,
            Exit_::class
        ];
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

        if ($node instanceof Exit_) {
            return [CollectedMethodCall::build(
                $node,
                $scope,
                '',
                'exit',
                CollectedMethodCall::TERMINATING_CALL
            )];
        }

        $calledClassName = $this->calledClassResolver->resolve($node, $scope);
        $calledMethodName = $this->nameResolver->resolve($node);

        if ($calledClassName === null || $calledMethodName === null) {
            return null;
        }

        if ($this->terminatingCallResolver->isTerminatingCallNode($node, $scope)) {
            return [CollectedMethodCall::build(
                $node,
                $scope,
                $calledClassName,
                $calledMethodName,
                CollectedMethodCall::TERMINATING_CALL
            )];
        }

        return [CollectedMethodCall::build(
            $node,
            $scope,
            $calledClassName,
            $calledMethodName
        )];
    }
}
