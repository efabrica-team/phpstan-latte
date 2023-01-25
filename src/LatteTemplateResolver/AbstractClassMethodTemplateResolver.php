<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Rules\RuleErrorBuilder;

abstract class AbstractClassMethodTemplateResolver extends AbstractClassTemplateResolver
{
    protected function getClassResult(ReflectionClass $reflectionClass, LatteContext $latteContext): LatteTemplateResolverResult
    {
        if ($reflectionClass->isAbstract() || $reflectionClass->isAnonymous()) {
            return new LatteTemplateResolverResult();
        }

        $result = new LatteTemplateResolverResult();
        foreach ($this->getMethodsMatching($reflectionClass, $this->getClassMethodPattern() . 'i') as $reflectionMethod) {
            if (!$reflectionMethod->isPublic()) {
                continue;
            }
            $templateContext = $this->getClassGlobalTemplateContext($reflectionClass, $latteContext)
                ->merge($latteContext->getMethodTemplateContext($reflectionClass->getName(), $reflectionMethod->getName()));

            $templateRenders = $latteContext->templateRenderFinder()->find($reflectionClass->getName(), $reflectionMethod->getName());
            $validTemplateRenders = array_filter($templateRenders, function (CollectedTemplateRender $templateRender) {
                return $templateRender->getTemplatePath() !== null;
            });
            if (count($validTemplateRenders) === 0) {
                if (!$latteContext->methodCallFinder()->hasAnyOutputCalls($reflectionClass->getName(), $reflectionMethod->getName()) &&
                    !$latteContext->methodCallFinder()->hasAnyTerminatingCalls($reflectionClass->getName(), $reflectionMethod->getName()) &&
                    !$latteContext->methodFinder()->hasAnyAlwaysTerminated($reflectionClass->getName(), $reflectionMethod->getName())
                ) {
                    $result->addErrorFromBuilder(RuleErrorBuilder::message("Cannot resolve latte template for {$reflectionClass->getShortName()}::{$reflectionMethod->getName()}().")
                        ->file($reflectionClass->getFileName() ?? 'unknown')
                        ->line($reflectionMethod->getStartLine()));
                }
            }
            foreach ($templateRenders as $templateRender) {
                $result->addTemplateFromRender($templateRender, $templateContext, $reflectionClass->getName(), $reflectionMethod->getName());
            }
        }
        return $result;
    }

    abstract protected function getClassMethodPattern(): string;
}
