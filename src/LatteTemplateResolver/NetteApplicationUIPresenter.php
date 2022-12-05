<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\RuleErrorBuilder;

final class NetteApplicationUIPresenter extends AbstractClassTemplateResolver
{
    use NetteApplicationUIPresenterGlobals;

    public function getSupportedClasses(): array
    {
        return ['Nette\Application\UI\Presenter'];
    }

    protected function getClassResult(ReflectionClass $reflectionClass, CollectedDataNode $collectedDataNode): LatteTemplateResolverResult
    {
        if ($reflectionClass->isAbstract() || $reflectionClass->isAnonymous()) {
            return new LatteTemplateResolverResult();
        }

        /** @var array<string, array{variables: Variable[], components: Component[], line: int, hasTerminatingCalls: bool}> $actions */
        $actions = [];
        foreach ($this->getMethodsMatching($reflectionClass, '/^(action|render).*/') as $reflectionMethod) {
            $actionName = lcfirst((string)preg_replace('/^(action|render)/i', '', $reflectionMethod->getName()));

            if (!isset($actions[$actionName])) {
                $actions[$actionName] = [
                    'variables' => $this->getClassGlobalVariables($reflectionClass),
                    'components' => $this->getClassGlobalComponents($reflectionClass),
                    'forms' => $this->getClassGlobalForms($reflectionClass),
                    'line' => $reflectionMethod->getStartLine(),
                    'hasTerminatingCalls' => false,
                ];
            }

            $actions[$actionName]['variables'] = array_merge($actions[$actionName]['variables'], $this->variableFinder->findByMethod($reflectionMethod));
            $actions[$actionName]['components'] = array_merge($actions[$actionName]['components'], $this->componentFinder->findByMethod($reflectionMethod));
            $actions[$actionName]['forms'] = array_merge($actions[$actionName]['forms'], $this->formFinder->findByMethod($reflectionMethod));
            $actions[$actionName]['hasTerminatingCalls'] = $actions[$actionName]['hasTerminatingCalls'] || $this->methodCallFinder->hasTerminatingCallsByMethod($reflectionMethod);
        }

        $result = new LatteTemplateResolverResult();
        foreach ($actions as $actionName => $actionDefinition) {
            $template = $this->findTemplateFilePath($reflectionClass, $actionName);
            if ($template === null) {
                if (!$actionDefinition['hasTerminatingCalls']) { // might not be rendered at all (for example redirect)
                    $result->addErrorFromBuilder(RuleErrorBuilder::message("Cannot resolve latte template for action $actionName")
                        ->file($reflectionClass->getFileName() ?? 'unknown')
                        ->line($actionDefinition['line'])
                        ->identifier($actionName));
                }
                continue;
            }
            $result->addTemplate(new Template($template, $reflectionClass->getName(), $actionName, $actionDefinition['variables'], $actionDefinition['components'], $actionDefinition['forms']));
        }

        return $result;
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
