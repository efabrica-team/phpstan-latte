<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedTemplatePath;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @phpstan-import-type CollectedTemplatePathArray from CollectedTemplatePath
 * @implements Collector<MethodCall, ?CollectedTemplatePathArray>
 */
final class TemplatePathCollector implements Collector
{
    private NameResolver $nameResolver;

    private TemplateTypeResolver $templateTypeResolver;

    private PathResolver $pathResolver;

    public function __construct(
        NameResolver $nameResolver,
        TemplateTypeResolver $templateTypeResolver,
        PathResolver $pathResolver
    ) {
        $this->nameResolver = $nameResolver;
        $this->templateTypeResolver = $templateTypeResolver;
        $this->pathResolver = $pathResolver;
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     * @phpstan-return null|CollectedTemplatePathArray
     */
    public function processNode(Node $node, Scope $scope): ?array
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

        $calledMethodName = $this->nameResolver->resolve($node->name);
        if (!in_array($calledMethodName, ['render', 'renderToString', 'setFile'], true)) {
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

        $path = $this->pathResolver->resolve($arg->value, $scope->getFile());
        if ($path === null || $path[0] !== '/') {
            return null;
        }
        return (new CollectedTemplatePath($actualClassName, $functionName, $path))->toArray();
    }
}
