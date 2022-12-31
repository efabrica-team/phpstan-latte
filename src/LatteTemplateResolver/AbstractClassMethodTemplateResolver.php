<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Rules\RuleErrorBuilder;

abstract class AbstractClassMethodTemplateResolver extends AbstractClassTemplateResolver
{
    protected function getClassResult(ReflectionClass $reflectionClass, LatteContextData $latteContext): LatteTemplateResolverResult
    {
        if ($reflectionClass->isAbstract() || $reflectionClass->isAnonymous()) {
            return new LatteTemplateResolverResult();
        }

        $globalVariables = $this->getClassGlobalVariables($reflectionClass);
        $globalComponents = $this->getClassGlobalComponents($reflectionClass);
        $globalForms = $this->getClassGlobalForms($reflectionClass);
        $globalFilters = $this->getClassGlobalFilters($reflectionClass);

        $result = new LatteTemplateResolverResult();
        foreach ($this->getMethodsMatching($reflectionClass, $this->getClassMethodPattern() . 'i') as $reflectionMethod) {
            $variables = array_merge($globalVariables, $this->variableFinder->find($reflectionClass->getName(), $reflectionMethod->getName()));
            $components = array_merge($globalComponents, $this->componentFinder->find($reflectionClass->getName(), $reflectionMethod->getName()));
            $forms = array_merge($globalForms, $this->formFinder->find($reflectionClass->getName(), $reflectionMethod->getName()));
            $filters = array_merge($globalFilters, $this->filterFinder->find($reflectionClass->getName(), $reflectionMethod->getName()));

            $templateRenders = $this->templateRenderFinder->find($reflectionClass->getName(), $reflectionMethod->getName());
            if (count($templateRenders) === 0) {
                if (!$this->methodCallFinder->hasAnyOutputCalls($reflectionClass->getName(), $reflectionMethod->getName()) &&
                    !$this->methodCallFinder->hasAnyTerminatingCalls($reflectionClass->getName(), $reflectionMethod->getName()) &&
                    !$this->methodFinder->hasAnyAlwaysTerminated($reflectionClass->getName(), $reflectionMethod->getName())
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
