<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedIncludePath;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ThisType;

/**
 * @phpstan-import-type CollectedIncludePathArray from CollectedIncludePath
 * @implements Collector<MethodCall, ?CollectedIncludePathArray>
 */
final class IncludePathCollector implements Collector
{
    private NameResolver $nameResolver;

    private ValueResolver $valueResolver;

    public function __construct(
        NameResolver $nameResolver,
        ValueResolver $valueResolver
    ) {
        $this->nameResolver = $nameResolver;
        $this->valueResolver = $valueResolver;
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     * @phpstan-return null|CollectedIncludePathArray
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

        $calledMethodName = $this->nameResolver->resolve($node->name);
        if ($calledMethodName !== 'createTemplate') {
            return null;
        }

        $callerType = $scope->getType($node->var);
        if (!$callerType instanceof ThisType) {
            return null;
        }
        $staticObjectType = $callerType->getStaticObjectType();

        if (!$staticObjectType->isInstanceOf('Latte\Runtime\Template')->yes()) {
            return null;
        }

        $includeTemplatePathArgument = $node->getArgs()[0] ?? null;
        if ($includeTemplatePathArgument === null) {
            return null;
        }

        $includeTemplatePath = $this->valueResolver->resolve($includeTemplatePathArgument->value);
        if (!is_string($includeTemplatePath)) {
            return null;
        }
        return (new CollectedIncludePath($includeTemplatePath, []))->toArray();
    }
}
