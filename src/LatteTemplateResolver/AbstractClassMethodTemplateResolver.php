<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

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

        $globalVariables = $this->getClassGlobalVariables($reflectionClass, $latteContext);
        $globalComponents = $this->getClassGlobalComponents($reflectionClass, $latteContext);
        $globalForms = $this->getClassGlobalForms($reflectionClass, $latteContext);
        $globalFilters = $this->getClassGlobalFilters($reflectionClass, $latteContext);

        $result = new LatteTemplateResolverResult();
        foreach ($this->getMethodsMatching($reflectionClass, $this->getClassMethodPattern() . 'i') as $reflectionMethod) {
            $variables = array_merge($globalVariables, $latteContext->variableFinder()->find($reflectionClass->getName(), $reflectionMethod->getName()));
            $components = array_merge($globalComponents, $latteContext->componentFinder()->find($reflectionClass->getName(), $reflectionMethod->getName()));
            $forms = array_merge($globalForms, $latteContext->formFinder()->find($reflectionClass->getName(), $reflectionMethod->getName()));
            $filters = array_merge($globalFilters, $latteContext->filterFinder()->find($reflectionClass->getName(), $reflectionMethod->getName()));

            $templateRenders = $latteContext->templateRenderFinder()->find($reflectionClass->getName(), $reflectionMethod->getName());
            if (count($templateRenders) === 0) {
                if (!$latteContext->methodCallFinder()->hasAnyOutputCalls($reflectionClass->getName(), $reflectionMethod->getName()) &&
                    !$latteContext->methodCallFinder()->hasAnyTerminatingCalls($reflectionClass->getName(), $reflectionMethod->getName()) &&
                    !$latteContext->methodFinder()->hasAnyAlwaysTerminated($reflectionClass->getName(), $reflectionMethod->getName())
                ) {
                    $result->addErrorFromBuilder(RuleErrorBuilder::message("Cannot resolve latte template for {$reflectionClass->getShortName()}::{$reflectionMethod->getName()}().")
                        ->file($reflectionClass->getFileName() ?? 'unknown')
                        ->line($reflectionMethod->getStartLine()));
                }
            }
            $result->addTemplatesFromRenders($templateRenders, $variables, $components, $forms, $filters, $reflectionClass->getName(), $reflectionMethod->getName());
        }
        return $result;
    }

    abstract protected function getClassMethodPattern(): string;
}
