<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedMethodCall;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\LatteTemplateResolver\NetteApplicationUIPresenter;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\CallResolver\CalledClassResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;

/**
 * @extends AbstractLatteContextCollector<CallLike, CollectedTemplateRender|CollectedMethodCall>
 */
final class NetteApplicationUIPresenterCollector extends AbstractLatteContextCollector
{
    private CalledClassResolver $calledClassResolver;

    private ValueResolver $valueResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        CalledClassResolver $calledClassResolver,
        ValueResolver $valueResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->calledClassResolver = $calledClassResolver;
        $this->valueResolver = $valueResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @param CallLike $node
     * @phpstan-return null|array<CollectedTemplateRender|CollectedMethodCall>
     */
    public function collectData(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $actualMethodName = $scope->getFunctionName();
        if ($actualMethodName === null) {
            return null;
        }

        if ($this->lattePhpDocResolver->resolveForNode($node, $scope)->isIgnored()) {
            return null;
        }

        $actualClassName = $classReflection->getName();
        $declaringClassName = $this->calledClassResolver->resolveDeclaring($node, $scope);
        $calledMethodName = $this->nameResolver->resolve($node);

        if ($declaringClassName === null || $calledMethodName === null) {
            return null;
        }

        if ((new ObjectType($declaringClassName))->isInstanceOf('Nette\Application\UI\Presenter')->no()) {
            return null;
        }

        if (in_array($calledMethodName, ['setView'], true)) {
            $views = $this->valueResolver->resolve($node->getArgs()[0]->value, $scope);
            if ($views === null) {
                return null;
            }
            $methodCalls = [];
            foreach ($views as $view) {
                if (!is_string($view)) {
                    continue;
                }
                $methodCalls[] = CollectedMethodCall::build(
                    $node,
                    $scope,
                    $declaringClassName,
                    $calledMethodName,
                    NetteApplicationUIPresenter::CALL_SET_VIEW,
                    ['view' => $view]
                );
            }
            return $methodCalls;
        }

        if (in_array($calledMethodName, ['sendTemplate'], true)) {
            $argument = $node->getArgs()[0]->value ?? null;
            if ($argument === null || $scope->getType($argument)->isNull()->maybe()) {
                return [CollectedTemplateRender::build($node, $scope, null)];
            } else {
                // cannot resolve automatically
                return [CollectedTemplateRender::build($node, $scope, false)];
            }
        }

        return null;
    }
}
