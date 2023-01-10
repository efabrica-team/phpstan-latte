<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\Finder\ComponentFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\FilterFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\FormFieldFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\FormFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\MethodCallFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\MethodFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\TemplatePathFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\TemplateRenderFinder;
use Efabrica\PHPStanLatte\LatteContext\Finder\VariableFinder;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;
use PHPStan\Reflection\ReflectionProvider;

final class LatteContext
{
    private LatteContextData $latteContextData;

    private ReflectionProvider $reflectionProvider;

    private PathResolver $pathResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    private MethodFinder $methodFinder;

    private MethodCallFinder $methodCallFinder;

    private VariableFinder $variableFinder;

    private ComponentFinder $componentFinder;

    private FilterFinder $filterFinder;

    private FormFieldFinder $formFieldFinder;

    private FormFinder $formFinder;

    private TemplatePathFinder $templatePathFinder;

    private TemplateRenderFinder $templateRenderFinder;

    public function __construct(LatteContextData $latteContextData, ReflectionProvider $reflectionProvider, PathResolver $pathResolver, LattePhpDocResolver $lattePhpDocResolver)
    {
        $this->latteContextData = $latteContextData;
        $this->reflectionProvider = $reflectionProvider;
        $this->pathResolver = $pathResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;

        $this->methodCallFinder = new MethodCallFinder($latteContextData, $this->reflectionProvider, $this->lattePhpDocResolver);
        $this->methodFinder = new MethodFinder($latteContextData, $this->methodCallFinder);
        $this->variableFinder = new VariableFinder($latteContextData, $this->methodCallFinder);
        $this->componentFinder = new ComponentFinder($latteContextData, $this->methodCallFinder);
        $this->filterFinder = new FilterFinder($latteContextData, $this->methodCallFinder);
        $this->formFieldFinder = new FormFieldFinder($latteContextData, $this->methodCallFinder);
        $this->formFinder = new FormFinder($latteContextData, $this->methodCallFinder, $this->formFieldFinder);
        $this->templatePathFinder = new TemplatePathFinder($latteContextData, $this->methodCallFinder, $this->methodFinder, $this->pathResolver);
        $this->templateRenderFinder = new TemplateRenderFinder($latteContextData, $this->methodCallFinder, $this->methodFinder, $this->templatePathFinder, $this->pathResolver);
    }

    public function getLatteContextData(): LatteContextData
    {
        return $this->latteContextData;
    }

    public function methodFinder(): MethodFinder
    {
        return $this->methodFinder;
    }

    public function methodCallFinder(): MethodCallFinder
    {
        return $this->methodCallFinder;
    }

    public function variableFinder(): VariableFinder
    {
        return $this->variableFinder;
    }

    public function componentFinder(): ComponentFinder
    {
        return $this->componentFinder;
    }

    public function filterFinder(): FilterFinder
    {
        return $this->filterFinder;
    }

    public function formFieldFinder(): FormFieldFinder
    {
        return $this->formFieldFinder;
    }

    public function formFinder(): FormFinder
    {
        return $this->formFinder;
    }

    public function templatePathFinder(): TemplatePathFinder
    {
        return $this->templatePathFinder;
    }

    public function templateRenderFinder(): TemplateRenderFinder
    {
        return $this->templateRenderFinder;
    }
}
