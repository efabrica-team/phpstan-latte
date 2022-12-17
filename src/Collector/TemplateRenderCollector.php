<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedTemplateRender;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Efabrica\PHPStanLatte\Template\Variable;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ThisType;

/**
 * @phpstan-import-type CollectedTemplateRenderArray from CollectedTemplateRender
 * @extends AbstractCollector<MethodCall, CollectedTemplateRender, CollectedTemplateRenderArray>
 */
final class TemplateRenderCollector extends AbstractCollector
{
    private ValueResolver $valueResolver;

    private PathResolver $pathResolver;

    private TemplateTypeResolver $templateTypeResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        TypeSerializer $typeSerializer,
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        Parser $parser,
        NodeScopeResolver $nodeScopeResolver,
        ValueResolver $valueResolver,
        PathResolver $pathResolver,
        TemplateTypeResolver $templateTypeResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($typeSerializer, $nameResolver, $reflectionProvider, $parser, $nodeScopeResolver);
        $this->valueResolver = $valueResolver;
        $this->pathResolver = $pathResolver;
        $this->templateTypeResolver = $templateTypeResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     * @phpstan-return null|CollectedTemplateRenderArray[]
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
     * @phpstan-return null|CollectedTemplateRenderArray[]
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
        $paths = $this->valueResolver->resolve($includeTemplatePathArgument->value, $scope);

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
     * @phpstan-return null|CollectedTemplateRenderArray[]
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

        $lattePhpDoc = $this->lattePhpDocResolver->resolveForNode($node, $scope);
        if ($lattePhpDoc->isIgnored()) {
            return null;
        }
        if ($lattePhpDoc->getTemplatePaths() !== null) {
            $paths = $lattePhpDoc->getTemplatePaths();
        }

        return $this->buildTemplateRenders($node, $scope, $paths, $variables);
    }

    /**
     * @param array<?string> $paths
     * @param Variable[] $variables
     * @return null|CollectedTemplateRenderArray[]
     */
    private function buildTemplateRenders(Node $node, Scope $scope, ?array $paths, array $variables): ?array
    {
        if ($paths === null) {
            return $this->collectItem(CollectedTemplateRender::build($node, $scope, false, $variables));
        }
        $templateRenders = [];
        foreach ($paths as $path) {
            $templateRenders[] = CollectedTemplateRender::build($node, $scope, $path, $variables);
        }
        return $this->collectItems($templateRenders);
    }

    /**
     * @return Variable[]
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
                $variables[] = new Variable($arrayKeyType->getValue(), $valueTypes[$k]);
            }
        }
        return $variables;
    }
}
