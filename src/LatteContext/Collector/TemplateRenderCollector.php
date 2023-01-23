<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedError;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ThisType;

/**
 * @extends AbstractLatteContextCollector<CollectedTemplateRender|CollectedError>
 */
final class TemplateRenderCollector extends AbstractLatteContextCollector
{
    private ValueResolver $valueResolver;

    private PathResolver $pathResolver;

    private TemplateTypeResolver $templateTypeResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        ValueResolver $valueResolver,
        PathResolver $pathResolver,
        TemplateTypeResolver $templateTypeResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->valueResolver = $valueResolver;
        $this->pathResolver = $pathResolver;
        $this->templateTypeResolver = $templateTypeResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     * @phpstan-return null|array<CollectedTemplateRender|CollectedError>
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

        $currentClassType = new ObjectType($classReflection->getName());

        if ($currentClassType->isInstanceOf('Latte\Runtime\Template')->yes()) {
            return $this->processNodeInCompiledLatte($node, $scope);
        } else {
            return $this->processNodeInPhp($node, $scope);
        }
    }

    /**
     * @param MethodCall $node
     * @phpstan-return null|array<CollectedTemplateRender|CollectedError>
     */
    public function processNodeInCompiledLatte(Node $node, Scope $scope): ?array
    {
        $calledMethodName = $this->nameResolver->resolve($node);
        if ($calledMethodName !== 'createTemplate') {
            return null;
        }

        $calledType = $scope->getType($node->var);
        if (!$calledType instanceof ThisType) {
            return null;
        }
        $staticObjectType = $calledType->getStaticObjectType();

        if (!$staticObjectType->isInstanceOf('Latte\Runtime\Template')->yes()) {
            return null;
        }

        $includeTemplatePathArgument = $node->getArgs()[0] ?? null;
        if ($includeTemplatePathArgument === null) {
            return null;
        }

        /** @var string[] $paths */
        $paths = $this->valueResolver->resolveStrings($includeTemplatePathArgument->value, $scope) ?? [null];

        $includeTemplateParamsArgument = $node->getArgs()[1] ?? null;

        if ($includeTemplateParamsArgument !== null &&
             $includeTemplateParamsArgument->value instanceof Plus &&
             $includeTemplateParamsArgument->value->left instanceof Array_
         ) {
            $variables = $this->buildVariables($includeTemplateParamsArgument->value->left, $scope);
        } else {
            $variables = [];
        }

        return $this->buildTemplateRenders($node, $scope, $paths, $variables);
    }

    /**
     * @param MethodCall $node
     * @phpstan-return null|array<CollectedTemplateRender|CollectedError>
     */
    public function processNodeInPhp(Node $node, Scope $scope): ?array
    {
        $calledMethodName = $this->nameResolver->resolve($node);
        if (!in_array($calledMethodName, ['render', 'renderToString'], true)) {
            return null;
        }

        $calledType = $scope->getType($node->var);
        if (!$this->templateTypeResolver->resolve($calledType)) {
            return null;
        }

        $templatePathArg = $node->getArgs()[0] ?? null;
        $templateVariablesArg = $node->getArgs()[1] ?? null;

        if ($templatePathArg === null || $templatePathArg->value === null || $scope->getType($templatePathArg->value)->isNull()->yes()) {
            $paths = [null]; // path not provided
        } else {
            $paths = $this->pathResolver->resolve($templatePathArg->value, $scope);
        }

        $variables = $this->buildVariables($templateVariablesArg->value ?? null, $scope);
        $components = [];

        $lattePhpDoc = $this->lattePhpDocResolver->resolveForNode($node, $scope);
        if ($lattePhpDoc->isIgnored()) {
            return null;
        }
        if ($lattePhpDoc->getTemplatePaths() !== []) {
            $paths = $lattePhpDoc->getTemplatePaths();
        }
        foreach ($lattePhpDoc->getVariables() as $name => $type) {
            $variables[$name] = new Variable($name, $type);
        }
        foreach ($lattePhpDoc->getComponents() as $name => $type) {
            $components[$name] = new Component($name, $type);
        }

        return $this->buildTemplateRenders($node, $scope, $paths, $variables, $components);
    }

    /**
     * @param array<?string> $paths
     * @param Variable[] $variables
     * @param Component[] $components
     * @return null|array<CollectedTemplateRender|CollectedError>
     */
    private function buildTemplateRenders(Node $node, Scope $scope, ?array $paths, array $variables, array $components = []): ?array
    {
        if ($paths === null) {
            return [CollectedError::build($node, $scope, 'Cannot automatically resolve latte template from expression.')];
        }
        $templateRenders = [];
        foreach ($paths as $path) {
            $templateRenders[] = CollectedTemplateRender::build($node, $scope, $path, $variables, $components);
        }
        return count($templateRenders) > 0 ? $templateRenders : null;
    }

    /**
     * @return array<string, Variable>
     */
    private function buildVariables(?Expr $argument, Scope $scope): array
    {
        if ($argument === null) {
            return [];
        }

        $argumentType = $scope->getType($argument);
        if ($argumentType instanceof ObjectType) {
            $argumentType = $argumentType->toArray();
        }

        $variables = [];
        if ($argumentType instanceof ConstantArrayType) {
            $keyTypes = $argumentType->getKeyTypes();
            $valueTypes = $argumentType->getValueTypes();
            foreach ($keyTypes as $k => $arrayKeyType) {
                if (!$arrayKeyType instanceof ConstantStringType) { // only string keys
                    continue;
                }
                $variableName = $arrayKeyType->getValue();
                $variables[$variableName] = new Variable($variableName, $valueTypes[$k]);
            }
        }
        return $variables;
    }
}
