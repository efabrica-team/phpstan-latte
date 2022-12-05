<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedTemplateRender;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Collector;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ThisType;

/**
 * @phpstan-import-type CollectedTemplateRenderArray from CollectedTemplateRender
 * @implements Collector<MethodCall, ?CollectedTemplateRenderArray[]>
 */
final class TemplateRenderCollector implements Collector
{
    private NameResolver $nameResolver;

    private ValueResolver $valueResolver;

    private PathResolver $pathResolver;

    private TemplateTypeResolver $templateTypeResolver;

    public function __construct(
        NameResolver $nameResolver,
        ValueResolver $valueResolver,
        PathResolver $pathResolver,
        TemplateTypeResolver $templateTypeResolver
    ) {
        $this->nameResolver = $nameResolver;
        $this->valueResolver = $valueResolver;
        $this->pathResolver = $pathResolver;
        $this->templateTypeResolver = $templateTypeResolver;
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     * @phpstan-return null|CollectedTemplateRenderArray[]
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
        $calledMethodName = $this->nameResolver->resolve($node->name);
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
        $calledMethodName = $this->nameResolver->resolve($node->name);
        if (!in_array($calledMethodName, ['render', 'renderToString'], true)) {
            return null;
        }

        $calledType = $scope->getType($node->var);
        if (!$this->templateTypeResolver->resolve($calledType)) {
            return null;
        }

        $templatePathArg = $node->getArgs()[0] ?? null;
        $tempalteVariablesArg = $node->getArgs()[1] ?? null;

        if ($templatePathArg === null || $templatePathArg->value === null || $scope->getType($templatePathArg->value)->isNull()->yes()) {
            $paths = [null]; // path not provided
        } else {
            $paths = $this->pathResolver->resolve($templatePathArg->value, $scope);
        }

        $variables = $this->buildVariables($tempalteVariablesArg->value ?? null, $scope);

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
            return [$this->buildTemplateRender($node, $scope, false, $variables)->toArray()];
        }
        $templateRenders = [];
        foreach ($paths as $path) {
            $templateRenders[] = $this->buildTemplateRender($node, $scope, $path, $variables)->toArray();
        }
        return count($templateRenders) > 0 ? $templateRenders : null;
    }

    /**
     * @param false|string|null $path
     * @param Variable[] $variables
     */
    private function buildTemplateRender(Node $node, Scope $scope, $path, array $variables): CollectedTemplateRender
    {
        return new CollectedTemplateRender(
            $path,
            $variables,
            $scope->getClassReflection() !== null ? $scope->getClassReflection()->getName() : '',
            $scope->getFunctionName() ?? '',
            $scope->getFile(),
            $node->getStartLine()
        );
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

  /**
   * @param array<CollectedData> $collectedDataList
   * @return CollectedTemplateRender[]
   */
    public function extractCollectedData(array $collectedDataList): array
    {
        $collectedTemplateRenders = [];
        foreach ($collectedDataList as $collectedData) {
            if ($collectedData->getCollectorType() !== TemplateRenderCollector::class) {
                continue;
            }
            /** @phpstan-var CollectedTemplateRenderArray[] $dataList */
            $dataList = $collectedData->getData();
            foreach ($dataList as $data) {
                $collectedTemplateRenders[] = CollectedTemplateRender::fromArray($data);
            }
        }
        return $collectedTemplateRenders;
    }
}
