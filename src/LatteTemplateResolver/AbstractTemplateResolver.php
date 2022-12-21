<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;
use Efabrica\PHPStanLatte\LatteContext\Finder\ComponentFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\FilterFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\FormFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\MethodCallFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\MethodFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\TemplatePathFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\TemplateRenderFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\VariableFinder;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;

abstract class AbstractTemplateResolver implements LatteTemplateResolverInterface
{
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

    public function __construct(PathResolver $pathResolver, LattePhpDocResolver $lattePhpDocResolver)
    {
        $this->pathResolver = $pathResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function resolve(CollectedResolvedNode $resolvedNode, LatteContextData $latteContext): LatteTemplateResolverResult
    {
        // TODO create factories?
        $this->methodCallFinder = new MethodCallFinder($latteContext);
        $this->methodFinder = new MethodFinder($latteContext, $this->methodCallFinder);
        $this->variableFinder = new VariableFinder($latteContext, $this->methodCallFinder);
        $this->componentFinder = new ComponentFinder($latteContext, $this->methodCallFinder);
        $this->filterFinder = new FilterFinder($latteContext, $this->methodCallFinder);
        $this->formFinder = new FormFinder($latteContext, $this->methodCallFinder);
        $this->templatePathFinder = new TemplatePathFinder($latteContext, $this->methodCallFinder, $this->pathResolver);
        $this->templateRenderFinder = new TemplateRenderFinder($latteContext, $this->methodCallFinder, $this->templatePathFinder, $this->pathResolver);

        return $this->getResult($resolvedNode, $latteContext);
    }

    /**
     * @return LatteTemplateResolverResult
     */
    abstract protected function getResult(CollectedResolvedNode $resolvedNode, LatteContextData $latteContext): LatteTemplateResolverResult;
}
