<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Template\Template;
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

        $className = $reflectionClass->getName();
        $shortClassName = $reflectionClass->getShortName();
        $globalVariables = $this->getClassGlobalVariables($reflectionClass);
        $globalComponents = $this->getClassGlobalComponents($reflectionClass);

        $result = new LatteTemplateResolverResult();
        foreach ($this->getMethodsMatching($reflectionClass, $this->getClassMethodPattern() . 'i') as $reflectionMethod) {
            $variables = array_merge($globalVariables, $this->variableFinder->findByMethod($reflectionMethod));
            $components = array_merge($globalComponents, $this->componentFinder->findByMethod($reflectionMethod));

            $templatePaths = $this->templatePathFinder->findByMethod($reflectionMethod);
            if (count($templatePaths) === 0) {
                $result->addErrorFromBuilder(RuleErrorBuilder::message("Cannot resolve latte template for {$shortClassName}::{$reflectionMethod->getName()}().")
                    ->file($reflectionClass->getFileName() ?? 'unknown')
                    ->line($reflectionMethod->getStartLine())
                    ->identifier($reflectionMethod->getName()));
            }
            foreach ($templatePaths as $templatePath) {
                if ($templatePath === null) {
                    // TODO exact file and line where failed expression is located
                    $result->addErrorFromBuilder(RuleErrorBuilder::message("Cannot automatically resolve latte template from expression inside {$shortClassName}::{$reflectionMethod->getName()}().")
                        ->file($reflectionClass->getFileName() ?? 'unknown')
                        ->line($reflectionMethod->getStartLine())
                        ->identifier($reflectionMethod->getName()));
                } else {
                    $result->addTemplate(new Template($templatePath, $className, $reflectionMethod->getName(), $variables, $components));
                }
            }
        }
        return $result;
    }

    abstract protected function getClassMethodPattern(): string;
}
