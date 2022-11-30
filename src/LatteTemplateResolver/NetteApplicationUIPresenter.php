<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Template\Template;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Node\CollectedDataNode;

final class NetteApplicationUIPresenter extends AbstractClassTemplateResolver
{
    use NetteApplicationUIPresenterGlobals;

    public function getSupportedClasses(): array
    {
        return ['Nette\Application\UI\Presenter'];
    }

    protected function getClassTemplates(ReflectionClass $reflectionClass, CollectedDataNode $collectedDataNode): array
    {
        $actions = [];
        foreach ($this->getMethodsMatching($reflectionClass, '/^(action|render).*/') as $reflectionMethod) {
            $actionName = lcfirst((string)preg_replace('/^(action|render)/i', '', $reflectionMethod->getName()));

            if (!isset($actions[$actionName])) {
                $actions[$actionName] = [
                    'variables' => $this->getClassGlobalVariables($reflectionClass),
                    'components' => $this->getClassGlobalComponents($reflectionClass),
                ];
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
            $templates[] = new Template($template, $reflectionClass->getName(), $actionDefinition['variables'], $actionDefinition['components']);
        }

        return $templates;
    }

    private function findTemplateFilePath(ReflectionClass $reflectionClass, string $actionName): ?string
    {
        $shortClassName = $reflectionClass->getShortName();
        $presenterName = str_replace('Presenter', '', $shortClassName);
        $dir = $this->getClassDir($reflectionClass);
        if ($dir === null) {
            return null;
        }

        $dir = is_dir("$dir/templates") ? $dir : dirname($dir);

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
