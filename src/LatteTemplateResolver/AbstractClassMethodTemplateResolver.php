<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\RuleErrorBuilder;

abstract class AbstractClassMethodTemplateResolver extends AbstractClassTemplateResolver
{
    protected function getClassResult(ReflectionClass $reflectionClass, CollectedDataNode $collectedDataNode): LatteTemplateResolverResult
    {
        if ($reflectionClass->isAbstract() || $reflectionClass->isAnonymous()) {
            return new LatteTemplateResolverResult();
        }

        $globalVariables = $this->getClassGlobalVariables($reflectionClass);
        $globalComponents = $this->getClassGlobalComponents($reflectionClass);
        $globalForms = $this->getClassGlobalForms($reflectionClass);

        $result = new LatteTemplateResolverResult();
        foreach ($this->getMethodsMatching($reflectionClass, $this->getClassMethodPattern() . 'i') as $reflectionMethod) {
            $variables = array_merge($globalVariables, $this->variableFinder->findByMethod($reflectionMethod));
            $components = array_merge($globalComponents, $this->componentFinder->findByMethod($reflectionMethod));
            $forms = array_merge($globalForms, $this->formFinder->findByMethod($reflectionMethod));

            $templateRenders = $this->templateRenderFinder->findByMethod($reflectionMethod);
            if (count($templateRenders) === 0) {
                if (!$this->methodCallFinder->hasAnyOutputCallsByMethod($reflectionMethod) &&
                    !$this->methodCallFinder->hasAnyTerminatingCallsByMethod($reflectionMethod) &&
                    !$this->methodFinder->hasAnyAlwaysTerminatedByMethod($reflectionMethod)
                ) {
                    $result->addErrorFromBuilder(RuleErrorBuilder::message("Cannot resolve latte template for {$reflectionClass->getShortName()}::{$reflectionMethod->getName()}().")
                        ->file($reflectionClass->getFileName() ?? 'unknown')
                        ->line($reflectionMethod->getStartLine()));
                }
            }
            $result->addTemplatesFromRenders($templateRenders, $variables, $components, $forms, $reflectionClass->getName(), $reflectionMethod->getName());
        }
        return $result;
    }

    abstract protected function getClassMethodPattern(): string;
}
