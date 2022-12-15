<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedTemplateRender;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;

/**
 * @phpstan-import-type CollectedTemplateRenderArray from CollectedTemplateRender
 * @extends AbstractCollector<ClassMethod, CollectedTemplateRender, CollectedTemplateRenderArray>
 */
final class TemplateRenderMethodPhpDocCollector extends AbstractCollector
{
    private NameResolver $nameResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(TypeSerializer $typeSerializer, NameResolver $nameResolver, LattePhpDocResolver $lattePhpDocResolver)
    {
        parent::__construct($typeSerializer);
        $this->nameResolver = $nameResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     * @phpstan-return null|CollectedTemplateRenderArray[]
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $methodName = $this->nameResolver->resolve($node);
        if ($methodName === null) {
            return null;
        }

        $classLattePhpDoc = $this->lattePhpDocResolver->resolveForClass($classReflection->getName());
        $lattePhpDoc = $this->lattePhpDocResolver->resolveForMethod($classReflection->getName(), $methodName);
        if ($lattePhpDoc->isIgnored()) {
            return null;
        }

        $templateRenders = [];
        foreach ($lattePhpDoc->getTemplatePaths() ?? [] as $templatePath) {
            $templateRenders[] = CollectedTemplateRender::build($node, $scope, $templatePath);
        }
        foreach ($classLattePhpDoc->getTemplatePaths() ?? [] as $templatePath) {
            $templateRenders[] = CollectedTemplateRender::build($node, $scope, $templatePath);
        }

        return $this->collectItems($templateRenders);
    }
}