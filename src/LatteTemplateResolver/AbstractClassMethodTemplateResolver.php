<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\RuleErrorBuilder;
use function array_filter;
use function count;

abstract class AbstractClassMethodTemplateResolver extends AbstractClassTemplateResolver
{
    protected function getClassResult(ClassReflection $classReflection, LatteContext $latteContext): LatteTemplateResolverResult
    {
        if ($classReflection->isAbstract() || $classReflection->isAnonymous()) {
            return new LatteTemplateResolverResult();
        }

        $result = new LatteTemplateResolverResult();
        foreach ($this->getMethodsMatching($classReflection, $this->getClassMethodPattern() . 'i') as $methodReflection) {
            if (!$methodReflection->isPublic()) {
                continue;
            }
            $methodName = $methodReflection->getName();
            $templateContext = $this->getClassGlobalTemplateContext($classReflection, $latteContext)
                ->merge($latteContext->getMethodTemplateContext($classReflection->getName(), $methodName));

            $templateRenders = $latteContext->templateRenderFinder()->find($classReflection->getName(), $methodName);
            $validTemplateRenders = array_filter($templateRenders, function (CollectedTemplateRender $templateRender) {
                return $templateRender->getTemplatePath() !== null;
            });
            if (count($validTemplateRenders) === 0) {
                if (!$latteContext->methodCallFinder()->hasAnyOutputCalls($classReflection->getName(), $methodName) &&
                    !$latteContext->methodCallFinder()->hasAnyTerminatingCalls($classReflection->getName(), $methodName) &&
                    !$latteContext->methodFinder()->hasAnyAlwaysTerminated($classReflection->getName(), $methodName)
                ) {
                    $result->addErrorFromBuilder(RuleErrorBuilder::message("Cannot resolve latte template for {$classReflection->getNativeReflection()->getShortName()}::{$methodName}().")
                        ->identifier('latte.cannotResolve')
                        ->file($classReflection->getFileName() ?? 'unknown')
                        ->line($this->getMethodStartLine($classReflection, $methodName)));
                }
            }
            foreach ($templateRenders as $templateRender) {
                $result->addTemplateFromRender($templateRender, $templateContext, $classReflection->getName(), $methodName);
            }
        }
        return $result;
    }

    abstract protected function getClassMethodPattern(): string;
}
