<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Type\ObjectType;

final class NetteApplicationUIPresenter extends AbstractClassTemplateResolver
{
    public function getSupportedClasses(): array
    {
        return ['Nette\Application\UI\Presenter'];
    }

    protected function getClassTemplates(ReflectionClass $reflectionClass, CollectedDataNode $collectedDataNode): array
    {
        $className = $reflectionClass->getName();
        $presenterType = new ObjectType($className);

        $globalVariables = array_merge(
            $this->variableFinder->find($className, 'startup'),
            $this->variableFinder->find($className, 'beforeRender'),
            [
                new Variable('actualClass', $presenterType),
                new Variable('presenter', $presenterType),
            ]
        );

        $globalComponents = array_merge(
            $this->componentFinder->find($className, ''),
            $this->componentFinder->find($className, 'startup'),
            $this->componentFinder->find($className, 'beforeRender')
        );

        $actions = [];

        foreach ($this->getMethodsMatching($reflectionClass, '/(action|render).*/') as $reflectionMethod) {
            $actionName = lcfirst(str_replace(['action', 'render'], '', $reflectionMethod->getName()));

            if (!isset($actions[$actionName])) {
                $actions[$actionName] = ['variables' => $globalVariables, 'components' => $globalComponents];
            }

            $actions[$actionName]['variables'] = array_merge($actions[$actionName]['variables'], $this->variableFinder->findByMethod($reflectionMethod));
            $actions[$actionName]['components'] = array_merge($actions[$actionName]['components'], $this->componentFinder->findByMethod($reflectionMethod));
        }

        $templates = [];
        foreach ($actions as $actionName => $actionDefinition) {
            $template = $this->findTemplateFilePath($reflectionClass, $actionName);
            if ($template === null) {
                continue;
            }
            $templates[] = new Template($template, $className, $actionDefinition['variables'], $actionDefinition['components']);
        }

        return $templates;
    }

    private function findTemplateFilePath(ReflectionClass $reflectionClass, string $actionName): ?string
    {
        $shortClassName = $reflectionClass->getShortName();
        $fileName = $reflectionClass->getFileName();
        if ($fileName === null) {
            return null;
        }
        $dir = dirname($fileName);

        $presenterName = str_replace('Presenter', '', $shortClassName);
        $dir = is_dir($dir . '/templates') ? $dir : dirname($dir);

        $templateFileCandidates = [
            $dir . '/templates/' . $presenterName . '/' . $actionName . '.latte',
            $dir . '/templates/' . $presenterName . '.' . $actionName . '.latte',
        ];

        foreach ($templateFileCandidates as $templateFileCandidate) {
            if (file_exists($templateFileCandidate)) {
                return $templateFileCandidate;
            }
        }

        return null;
    }
}
