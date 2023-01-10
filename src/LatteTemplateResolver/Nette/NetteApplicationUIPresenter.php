<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver\Nette;

use Efabrica\PHPStanLatte\Helper\VariablesHelper;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\LatteContext\Resolver\LatteContextResolverInterface;
use Efabrica\PHPStanLatte\LatteContext\Resolver\Nette\NetteApplicationUIPresenterLatteContextResolver;
use Efabrica\PHPStanLatte\LatteTemplateResolver\AbstractClassTemplateResolver;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverResult;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Filter;
use Efabrica\PHPStanLatte\Template\Form\Form;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @uses Variable
 * @uses Component
 * @uses Form
 * @uses Filter
 * @uses CollectedTemplateRender
 * @phpstan-type ActionDefinition array{variables: Variable[], components: Component[], forms: Form[], filters: Filter[], line: int, renders: CollectedTemplateRender[], defaultTemplate: ?string, templatePaths: array<?string>, terminated: bool}
 */
final class NetteApplicationUIPresenter extends AbstractClassTemplateResolver
{
    public const CALL_SET_VIEW = 'Nette\Application\UI\Presenter::setView';

    public function getSupportedClasses(): array
    {
        return ['Nette\Application\UI\Presenter'];
    }

    protected function getClassContextResolver(ReflectionClass $reflectionClass, LatteContext $latteContext): LatteContextResolverInterface
    {
        return new NetteApplicationUIPresenterLatteContextResolver($reflectionClass, $latteContext);
    }

    protected function getClassResult(ReflectionClass $reflectionClass, LatteContext $latteContext): LatteTemplateResolverResult
    {
        if ($reflectionClass->isAbstract() || $reflectionClass->isAnonymous()) {
            return new LatteTemplateResolverResult();
        }

        /** @var ActionDefinition[] $actions */
        $actions = [];

        // action methods - including matching render methods
        foreach ($this->getMethodsMatching($reflectionClass, '/^action.*/') as $reflectionMethod) {
            $actionName = lcfirst((string)preg_replace('/^action/i', '', $reflectionMethod->getName()));

            if (!isset($actions[$actionName])) {
                $actions[$actionName] = $actions[$actionName] = $this->createActionDefinition($reflectionClass, $latteContext, $actionName);
            }
            $this->updateActionDefinitionByMethod($actions[$actionName], $reflectionClass, $reflectionMethod, $latteContext);

            // alternative renders (changed by setView in startup or action* method)
            $setViewCalls = array_merge(
                $latteContext->methodCallFinder()->findCalledOfType($reflectionClass->getName(), $reflectionMethod->getName(), self::CALL_SET_VIEW),
                $latteContext->methodCallFinder()->findCalledOfType($reflectionClass->getName(), 'startup', self::CALL_SET_VIEW)
            );
            $defaultRenderReached = true;
            foreach ($setViewCalls as $setViewCall) {
                $view = (string)$setViewCall->getParams()['view'];
                $actionViewName = $actionName . "($view)";
                $actions[$actionViewName] = $actions[$actionName];
                $actions[$actionViewName]['defaultTemplate'] = $this->findDefaultTemplateFilePath($reflectionClass, $view);
                $renderMethod = $reflectionClass->getMethod('render' . ucfirst($view));
                if ($renderMethod !== null) {
                    $this->updateActionDefinitionByMethod($actions[$actionViewName], $reflectionClass, $renderMethod, $latteContext);
                }
                if (!$setViewCall->isCalledConditionally()) {
                    $defaultRenderReached = false;
                }
            }

            if ($defaultRenderReached) {
                $renderMethod = $reflectionClass->getMethod('render' . ucfirst($actionName));
                if ($renderMethod !== null) {
                    $this->updateActionDefinitionByMethod($actions[$actionName], $reflectionClass, $renderMethod, $latteContext);
                }
            } else {
                unset($actions[$actionName]); // view is always changed
            }
        }

        // render methods without matching action method
        foreach ($this->getMethodsMatching($reflectionClass, '/^render.*/') as $reflectionMethod) {
            $actionName = lcfirst((string)preg_replace('/^render/i', '', $reflectionMethod->getName()));

            if (!isset($actions[$actionName])) {
                $actions[$actionName] = $this->createActionDefinition($reflectionClass, $latteContext, $actionName);
            }
            $this->updateActionDefinitionByMethod($actions[$actionName], $reflectionClass, $reflectionMethod, $latteContext);
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
            if ($actionDefinition['defaultTemplate'] === null) {
                if (!$actionDefinition['terminated'] && $actionDefinition['templatePaths'] === []) { // might not be rendered at all (for example redirect or use set template path)
                    $result->addErrorFromBuilder(RuleErrorBuilder::message("Cannot resolve latte template for action $actionName")
                        ->file($reflectionClass->getFileName() ?? 'unknown')
                        ->line($actionDefinition['line'])
                        ->identifier($actionName));
                }
                continue;
            }
            $result->addTemplate(new Template(
                $actionDefinition['defaultTemplate'],
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

    /**
     * @phpstan-return ActionDefinition
     */
    private function createActionDefinition(ReflectionClass $reflectionClass, LatteContext $latteContext, string $actionName): array
    {
        return [
            'variables' => $this->getClassGlobalVariables($reflectionClass, $latteContext),
            'components' => $this->getClassGlobalComponents($reflectionClass, $latteContext),
            'forms' => $this->getClassGlobalForms($reflectionClass, $latteContext),
            'filters' => $this->getClassGlobalFilters($reflectionClass, $latteContext),
            'line' => -1,
            'renders' => [],
            'defaultTemplate' => $this->findDefaultTemplateFilePath($reflectionClass, $actionName),
            'templatePaths' => [],
            'terminated' => false,
        ];
    }

    /**
     * @phpstan-param ActionDefinition $actionDefinition
     */
    private function updateActionDefinitionByMethod(&$actionDefinition, ReflectionClass $reflectionClass, ReflectionMethod $reflectionMethod, LatteContext $latteContext): void
    {
        $actionDefinition['line'] = $reflectionMethod->getStartLine();
        $actionDefinition['variables'] = VariablesHelper::union($actionDefinition['variables'], $latteContext->variableFinder()->find($reflectionClass->getName(), $reflectionMethod->getName()));
        $actionDefinition['components'] = array_merge($actionDefinition['components'], $latteContext->componentFinder()->find($reflectionClass->getName(), $reflectionMethod->getName()));
        $actionDefinition['forms'] = array_merge($actionDefinition['forms'], $latteContext->formFinder()->find($reflectionClass->getName(), $reflectionMethod->getName()));
        $actionDefinition['filters'] = array_merge($actionDefinition['filters'], $latteContext->filterFinder()->find($reflectionClass->getName(), $reflectionMethod->getName()));
        $actionDefinition['renders'] = array_merge($actionDefinition['renders'], $latteContext->templateRenderFinder()->find($reflectionClass->getName(), $reflectionMethod->getName()));
        $actionDefinition['templatePaths'] = array_merge($actionDefinition['templatePaths'], $latteContext->templatePathFinder()->find($reflectionClass->getName(), $reflectionMethod->getName()));
        $actionDefinition['terminated'] = $actionDefinition['terminated'] || $latteContext->methodCallFinder()->hasAnyTerminatingCalls($reflectionClass->getName(), $reflectionMethod->getName());
        $actionDefinition['terminated'] = $actionDefinition['terminated'] || $latteContext->methodFinder()->hasAnyAlwaysTerminated($reflectionClass->getName(), $reflectionMethod->getName());
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
