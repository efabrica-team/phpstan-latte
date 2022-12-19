<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedTemplatePath;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends AbstractLatteContextCollector<MethodCall, CollectedTemplatePath>
 */
final class TemplatePathCollector extends AbstractLatteContextCollector
{
    private TemplateTypeResolver $templateTypeResolver;

    private PathResolver $pathResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        TemplateTypeResolver $templateTypeResolver,
        PathResolver $pathResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->templateTypeResolver = $templateTypeResolver;
        $this->pathResolver = $pathResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     * @phpstan-return null|CollectedTemplatePath[]
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

        $actualClassName = $classReflection->getName();

        $calledMethodName = $this->nameResolver->resolve($node);
        if (!in_array($calledMethodName, ['setFile'], true)) {
            return null;
        }

        $callerType = $scope->getType($node->var);
        if (!$this->templateTypeResolver->resolve($callerType)) {
            return null;
        }

        $arg = $node->getArgs()[0] ?? null;
        if (!$arg) {
            return null;
        }

        if ($this->lattePhpDocResolver->resolveForNode($node, $scope)->isIgnored()) {
            return null;
        }

        $paths = $this->pathResolver->resolve($arg->value, $scope);
        if ($paths === null) {
            // failed to resolve
            return [new CollectedTemplatePath($actualClassName, $functionName, null)];
        }
        $templatePaths = [];
        foreach ($paths as $path) {
            $templatePaths[] = new CollectedTemplatePath($actualClassName, $functionName, $path);
        }
        return $templatePaths;
    }
}
