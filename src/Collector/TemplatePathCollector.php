<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedTemplatePath;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<MethodCall, ?CollectedTemplatePathArray>
 * @phpstan-import-type CollectedTemplatePathArray from CollectedTemplatePath
 */
final class TemplatePathCollector implements Collector
{
    private NameResolver $nameResolver;

    private TemplateTypeResolver $templateTypeResolver;

    private ValueResolver $valueResolver;

    public function __construct(
        NameResolver $nameResolver,
        TemplateTypeResolver $templateTypeResolver,
        ValueResolver $valueResolver
    ) {
        $this->nameResolver = $nameResolver;
        $this->templateTypeResolver = $templateTypeResolver;
        $this->valueResolver = $valueResolver;
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

        /** @var string|null $path */
        $path = $this->valueResolver->resolve($arg->value, $scope->getFile());
        if ($path === null) {
            return null;
        }
        return (new CollectedTemplatePath($actualClassName, $functionName, $path))->toArray();
    }
}
