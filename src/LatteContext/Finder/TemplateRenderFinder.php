<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;

final class TemplateRenderFinder
{
    /** @var array<string, array<string, CollectedTemplateRender[]>> */
    private array $collectedTemplateRenders = [];

    private MethodCallFinder $methodCallFinder;

    private MethodFinder $methodFinder;

    private TemplatePathFinder $templatePathFinder;

    private PathResolver $pathResolver;

    public function __construct(LatteContextData $latteContext, MethodCallFinder $methodCallFinder, MethodFinder $methodFinder, TemplatePathFinder $templatePathFinder, PathResolver $pathResolver)
    {
        $this->methodCallFinder = $methodCallFinder;
        $this->methodFinder = $methodFinder;
        $this->templatePathFinder = $templatePathFinder;
        $this->pathResolver = $pathResolver;

        $collectedTemplateRenders = $latteContext->getCollectedData(CollectedTemplateRender::class);
        foreach ($collectedTemplateRenders as $collectedTemplateRender) {
            $className = $collectedTemplateRender->getClassName();
            $methodName = $collectedTemplateRender->getMethodName();
            if (!isset($this->collectedTemplateRenders[$className][$methodName])) {
                $this->collectedTemplateRenders[$className][$methodName] = [];
            }
            $collectedTemplatePath = $collectedTemplateRender->getTemplatePath();
            $templatePaths = $this->pathResolver->expand($collectedTemplatePath, $this->methodFinder);
            if ($templatePaths !== null) {
                foreach ($templatePaths as $templatePath) {
                    $this->collectedTemplateRenders[$className][$methodName][] = $collectedTemplateRender->withTemplatePath($templatePath);
                }
            } else {
                $this->collectedTemplateRenders[$className][$methodName][] = $collectedTemplateRender->withTemplatePath(null);
            }
        }
    }

    /**
     * @param class-string $className
     * @return CollectedTemplateRender[]
     */
    public function find(string $className, string $methodName): array
    {
        $templateRenders = $this->findInMethodCalls($className, $methodName);

        $defaultTemplatePaths = $this->templatePathFinder->find($className, $methodName);

        $templateRendersWithTemplatePaths = [];
        foreach ($templateRenders as $templateRender) {
            // when render call does not specify template directly use default template(s) collected from setFile() calls
            if ($templateRender->getTemplatePath() === null && count($defaultTemplatePaths) > 0) {
                foreach ($defaultTemplatePaths as $defaultTemplatePath) {
                    $templateRendersWithTemplatePaths[] = $templateRender->withTemplatePath($defaultTemplatePath);
                }
            } else {
                $templateRendersWithTemplatePaths[] = $templateRender;
            }
        }
        return $templateRendersWithTemplatePaths;
    }

    /**
     * @param class-string $className
     * @param ?class-string $currentClassName
     * @return CollectedTemplateRender[]
     */
    private function findInMethodCalls(string $className, string $methodName, string $currentClassName = null): array
    {
        $callback = function (string $declaringClass, string $methodName, array $fromCalled) {
            return array_merge($this->collectedTemplateRenders[$declaringClass][$methodName] ?? [], ...$fromCalled);
        };
        return $this->methodCallFinder->traverseCalled($callback, $className, $methodName, $currentClassName);
    }
}
