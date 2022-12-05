<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Collector\Finder\ComponentFinder;
use Efabrica\PHPStanLatte\Collector\Finder\FormFinder;
use Efabrica\PHPStanLatte\Collector\Finder\MethodCallFinder;
use Efabrica\PHPStanLatte\Collector\Finder\TemplatePathFinder;
use Efabrica\PHPStanLatte\Collector\Finder\TemplateRenderFinder;
use Efabrica\PHPStanLatte\Collector\Finder\VariableFinder;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedNode;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;
use PHPStan\Node\CollectedDataNode;

abstract class AbstractTemplateResolver implements LatteTemplateResolverInterface
{
    private PathResolver $pathResolver;

    protected MethodCallFinder $methodCallFinder;

    protected VariableFinder $variableFinder;

    protected ComponentFinder $componentFinder;

    protected FormFinder $formFinder;

    protected TemplatePathFinder $templatePathFinder;

    protected TemplateRenderFinder $templateRenderFinder;

    public function __construct(PathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }

    public function resolve(CollectedResolvedNode $resolvedNode, CollectedDataNode $collectedDataNode): LatteTemplateResolverResult
    {
        // TODO create factories?
        $this->methodCallFinder = new MethodCallFinder($collectedDataNode);
        $this->variableFinder = new VariableFinder($collectedDataNode, $this->methodCallFinder);
        $this->componentFinder = new ComponentFinder($collectedDataNode, $this->methodCallFinder);
        $this->formFinder = new FormFinder($collectedDataNode, $this->methodCallFinder);
        $this->templatePathFinder = new TemplatePathFinder($collectedDataNode, $this->methodCallFinder, $this->pathResolver);
        $this->templateRenderFinder = new TemplateRenderFinder($collectedDataNode, $this->methodCallFinder, $this->templatePathFinder, $this->pathResolver);

        return $this->getResult($resolvedNode, $collectedDataNode);
    }

    /**
     * @return LatteTemplateResolverResult
     */
    abstract protected function getResult(CollectedResolvedNode $resolvedNode, CollectedDataNode $collectedDataNode): LatteTemplateResolverResult;
}
