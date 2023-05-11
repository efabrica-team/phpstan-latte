<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedError;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\LatteContext\Collector\TemplateRenderCollector\TemplateRenderCollectorInterface;
use Efabrica\PHPStanLatte\LatteContext\LatteContextHelper;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;

/**
 * @extends AbstractLatteContextCollector<CollectedTemplateRender|CollectedError>
 */
final class TemplateRenderCollector extends AbstractLatteContextCollector
{
    private ValueResolver $valueResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    /** @var TemplateRenderCollectorInterface[] */
    private array $templateRenderCollectors;

    /**
     * @param TemplateRenderCollectorInterface[] $templateRenderCollectors
     */
    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        ValueResolver $valueResolver,
        LattePhpDocResolver $lattePhpDocResolver,
        array $templateRenderCollectors
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->valueResolver = $valueResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
        $this->templateRenderCollectors = $templateRenderCollectors;
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

        if (!LatteContextHelper::isClass($node, $scope, 'Latte\Runtime\Template')) {
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
            $variables = LatteContextHelper::variablesFromExpr($includeTemplateParamsArgument->value->left, $scope);
        } else {
            $variables = [];
        }

        return CollectedTemplateRender::buildAll($node, $scope, $paths, $variables);
    }

    /**
     * @param MethodCall $node
     * @phpstan-return null|array<CollectedTemplateRender|CollectedError>
     */
    public function processNodeInPhp(Node $node, Scope $scope): ?array
    {
        $isTemplateRenderNode = false;
        /** @var CollectedTemplateRender[] $collectedTemplateRenders */
        $collectedTemplateRenders = [];

        foreach ($this->templateRenderCollectors as $templateRenderCollector) {
            if (!$templateRenderCollector->isSupported($node)) {
                continue;
            }
            $templateRenders = $templateRenderCollector->collect($node, $scope);
            if ($templateRenders === null) {
                continue;
            }
            $isTemplateRenderNode = true;
            $collectedTemplateRenders = array_merge($collectedTemplateRenders, $templateRenders);
        }

        if (!$isTemplateRenderNode) {
            return null;
        }

        $lattePhpDoc = $this->lattePhpDocResolver->resolveForNode($node, $scope);
        if ($lattePhpDoc->isIgnored()) {
            return null;
        }

        $templateRenders = [];
        foreach ($collectedTemplateRenders as $collectedTemplateRender) {
            if ($collectedTemplateRender instanceof CollectedError) {
                $templateRenders[] = $collectedTemplateRender;
                continue;
            }
            $variables = [];
            foreach ($collectedTemplateRender->getVariables() as $variable) {
                $variables[$variable->getName()] = $variable;
            }
            foreach ($lattePhpDoc->getVariables() as $name => $type) {
                $variables[$name] = new Variable($name, $type);
            }
            $components = [];
            foreach ($collectedTemplateRender->getComponents() as $component) {
                $components[$component->getName()] = $component;
            }
            foreach ($lattePhpDoc->getComponents() as $name => $type) {
                $components[$name] = new Component($name, $type);
            }
            $templateRenders[] = CollectedTemplateRender::build($node, $scope, $collectedTemplateRender->getTemplatePath(), $variables, $components);
        }

        return count($templateRenders) > 0 ? $templateRenders : null;
    }
}
