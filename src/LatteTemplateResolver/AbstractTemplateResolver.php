<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Collector\Finder\ComponentFinder;
use Efabrica\PHPStanLatte\Collector\Finder\FilterFinder;
use Efabrica\PHPStanLatte\Collector\Finder\FormFinder;
use Efabrica\PHPStanLatte\Collector\Finder\MethodCallFinder;
use Efabrica\PHPStanLatte\Collector\Finder\MethodFinder;
use Efabrica\PHPStanLatte\Collector\Finder\TemplatePathFinder;
use Efabrica\PHPStanLatte\Collector\Finder\TemplateRenderFinder;
use Efabrica\PHPStanLatte\Collector\Finder\VariableFinder;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedNode;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PHPStan\Node\CollectedDataNode;

abstract class AbstractTemplateResolver implements LatteTemplateResolverInterface
{
    private TypeSerializer $typeSerializer;

    private PathResolver $pathResolver;

    protected LattePhpDocResolver $lattePhpDocResolver;

    protected MethodFinder $methodFinder;

    protected MethodCallFinder $methodCallFinder;

    protected VariableFinder $variableFinder;

    protected ComponentFinder $componentFinder;

    protected FilterFinder $filterFinder;

    protected FormFinder $formFinder;

    protected TemplatePathFinder $templatePathFinder;

    protected TemplateRenderFinder $templateRenderFinder;

    public function __construct(TypeSerializer $typeSerializer, PathResolver $pathResolver, LattePhpDocResolver $lattePhpDocResolver)
    {
        $this->typeSerializer = $typeSerializer;
        $this->pathResolver = $pathResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function resolve(CollectedResolvedNode $resolvedNode, CollectedDataNode $collectedDataNode): LatteTemplateResolverResult
    {
        // TODO create factories?
        $this->methodCallFinder = new MethodCallFinder($collectedDataNode, $this->typeSerializer);
        $this->methodFinder = new MethodFinder($collectedDataNode, $this->typeSerializer, $this->methodCallFinder);
        $this->variableFinder = new VariableFinder($collectedDataNode, $this->typeSerializer, $this->methodCallFinder);
        $this->componentFinder = new ComponentFinder($collectedDataNode, $this->typeSerializer, $this->methodCallFinder);
        $this->filterFinder = new FilterFinder($collectedDataNode, $this->typeSerializer, $this->methodCallFinder);
        $this->formFinder = new FormFinder($collectedDataNode, $this->typeSerializer, $this->methodCallFinder);
        $this->templatePathFinder = new TemplatePathFinder($collectedDataNode, $this->typeSerializer, $this->methodCallFinder, $this->pathResolver);
        $this->templateRenderFinder = new TemplateRenderFinder($collectedDataNode, $this->typeSerializer, $this->methodCallFinder, $this->templatePathFinder, $this->pathResolver);

        return $this->getResult($resolvedNode, $collectedDataNode);
    }

    /**
     * @return LatteTemplateResolverResult
     */
    abstract protected function getResult(CollectedResolvedNode $resolvedNode, CollectedDataNode $collectedDataNode): LatteTemplateResolverResult;
}
