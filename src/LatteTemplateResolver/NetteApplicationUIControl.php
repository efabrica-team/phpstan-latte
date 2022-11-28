<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Type\ObjectType;

final class NetteApplicationUIControl extends AbstractClassTemplateResolver
{
    public function getSupportedClasses(): array
    {
        return ['Nette\Application\UI\Control'];
    }

    protected function getClassTemplates(ReflectionClass $reflectionClass, CollectedDataNode $collectedDataNode): array
    {
        $className = $reflectionClass->getName();

        $controlType = new ObjectType($className);
        $globalVariables = [
            new Variable('actualClass', $controlType),
            new Variable('presenter', $controlType),
        ];

        $globalComponents = $this->componentFinder->find($reflectionClass->getName(), '');

        $templates = [];
        foreach ($this->getMethodsMatching($reflectionClass, '/render.*/') as $reflectionMethod) {
            $variables = array_merge($globalVariables, $this->variableFinder->findByMethod($reflectionMethod));
            $components = array_merge($globalComponents, $this->componentFinder->findByMethod($reflectionMethod));

            $templatePaths = $this->templatePathFinder->findByMethod($reflectionMethod);
            foreach ($templatePaths as $templatePath) {
                $templates[] = new Template($templatePath, $reflectionClass->getName(), $variables, $components);
            }
        }
        return $templates;
    }
}
