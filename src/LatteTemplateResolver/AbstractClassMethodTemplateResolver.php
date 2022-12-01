<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Template\Template;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Node\CollectedDataNode;

abstract class AbstractClassMethodTemplateResolver extends AbstractClassTemplateResolver
{
    protected function getClassTemplates(ReflectionClass $reflectionClass, CollectedDataNode $collectedDataNode): array
    {
        $globalVariables = $this->getClassGlobalVariables($reflectionClass);
        $globalComponents = $this->getClassGlobalComponents($reflectionClass);

        $templates = [];
        foreach ($this->getMethodsMatching($reflectionClass, $this->getClassMethodPattern() . 'i') as $reflectionMethod) {
            $variables = array_merge($globalVariables, $this->variableFinder->findByMethod($reflectionMethod));
            $components = array_merge($globalComponents, $this->componentFinder->findByMethod($reflectionMethod));

            $templatePaths = $this->templatePathFinder->findByMethod($reflectionMethod);
            foreach ($templatePaths as $templatePath) {
                $templates[] = new Template($templatePath, $reflectionClass->getName(), $variables, $components);
            }
        }
        return $templates;
    }

    abstract protected function getClassMethodPattern(): string;
}
