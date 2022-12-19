<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedForm;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedTemplateRender;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Filter;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Rules\RuleErrorBuilder;

final class NetteApplicationUIPresenter extends AbstractClassTemplateResolver
{
    use NetteApplicationUIPresenterGlobals;

    public function getSupportedClasses(): array
    {
        return ['Nette\Application\UI\Presenter'];
    }

    protected function getClassResult(ReflectionClass $reflectionClass, LatteContextData $latteContext): LatteTemplateResolverResult
    {
        if ($reflectionClass->isAbstract() || $reflectionClass->isAnonymous()) {
            return new LatteTemplateResolverResult();
        }

        /** @var array<string, array{variables: Variable[], components: Component[], forms: CollectedForm[], filters: Filter[], line: int, renders: CollectedTemplateRender[], templatePaths: array<?string>, terminated: bool}> $actions */
        $actions = [];
        foreach ($this->getMethodsMatching($reflectionClass, '/^(action|render).*/') as $reflectionMethod) {
            $actionName = lcfirst((string)preg_replace('/^(action|render)/i', '', $reflectionMethod->getName()));

            if (!isset($actions[$actionName])) {
                $actions[$actionName] = [
                    'variables' => $this->getClassGlobalVariables($reflectionClass),
                    'components' => $this->getClassGlobalComponents($reflectionClass),
                    'forms' => $this->getClassGlobalForms($reflectionClass),
                    'filters' => $this->getClassGlobalFilters($reflectionClass),
                    'line' => $reflectionMethod->getStartLine(),
                    'renders' => $this->templateRenderFinder->findByMethod($reflectionMethod),
                    'templatePaths' => $this->templatePathFinder->findByMethod($reflectionMethod),
                    'terminated' => false,
                ];
            }

            $actions[$actionName]['variables'] = array_merge($actions[$actionName]['variables'], $this->variableFinder->findByMethod($reflectionMethod));
            $actions[$actionName]['components'] = array_merge($actions[$actionName]['components'], $this->componentFinder->findByMethod($reflectionMethod));
            $actions[$actionName]['forms'] = array_merge($actions[$actionName]['forms'], $this->formFinder->findByMethod($reflectionMethod));
            $actions[$actionName]['filters'] = array_merge($actions[$actionName]['filters'], $this->filterFinder->findByMethod($reflectionMethod));
            $actions[$actionName]['renders'] = array_merge($actions[$actionName]['renders'], $this->templateRenderFinder->findByMethod($reflectionMethod));
            $actions[$actionName]['templatePaths'] = array_merge($actions[$actionName]['templatePaths'], $this->templatePathFinder->findByMethod($reflectionMethod));
            $actions[$actionName]['terminated'] = $actions[$actionName]['terminated'] || $this->methodCallFinder->hasAnyTerminatingCallsByMethod($reflectionMethod);
            $actions[$actionName]['terminated'] = $actions[$actionName]['terminated'] || $this->methodFinder->hasAnyAlwaysTerminatedByMethod($reflectionMethod);
        }

        $result = new LatteTemplateResolverResult();
        foreach ($actions as $actionName => $actionDefinition) {
            // explicit render calls
            $result->addTemplatesFromRenders(
                $actionDefinition['renders'],
                $actionDefinition['variables'],
                $actionDefinition['components'],
                $actionDefinition['forms'],
                $actionDefinition['filters'],
                $reflectionClass->getName(),
                $actionName
            );

            // default render with set template path
            foreach ($actionDefinition['templatePaths'] as $template) {
                // TODO better location of unresolved expression - must become part of CollectedTemplatePath and CollectedTemplatePathFinder must return ValueObject not only strings
                if ($template === null) {
                    $result->addErrorFromBuilder(RuleErrorBuilder::message('Cannot automatically resolve latte template from expression.')
                    ->file($reflectionClass->getFileName() ?? 'unknown')
                    ->line($actionDefinition['line']));
                    continue;
                }
                $result->addTemplate(new Template(
                    $template,
                    $reflectionClass->getName(),
                    $actionName,
                    $actionDefinition['variables'],
                    $actionDefinition['components'],
                    $actionDefinition['forms'],
                    $actionDefinition['filters'],
                ));
            }

            // default render with default template
            $template = $this->findDefaultTemplateFilePath($reflectionClass, $actionName);
            if ($template === null) {
                if (!$actionDefinition['terminated'] && $actionDefinition['templatePaths'] === []) { // might not be rendered at all (for example redirect or use set template path)
                    $result->addErrorFromBuilder(RuleErrorBuilder::message("Cannot resolve latte template for action $actionName")
                        ->file($reflectionClass->getFileName() ?? 'unknown')
                        ->line($actionDefinition['line'])
                        ->identifier($actionName));
                }
                continue;
            }
            $result->addTemplate(new Template(
                $template,
                $reflectionClass->getName(),
                $actionName,
                $actionDefinition['variables'],
                $actionDefinition['components'],
                $actionDefinition['forms'],
                $actionDefinition['filters'],
            ));
        }

        return $result;
    }

    private function findDefaultTemplateFilePath(ReflectionClass $reflectionClass, string $actionName): ?string
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
