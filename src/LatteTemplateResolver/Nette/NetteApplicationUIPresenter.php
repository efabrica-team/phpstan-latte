<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver\Nette;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\LatteContext\Resolver\LatteContextResolverInterface;
use Efabrica\PHPStanLatte\LatteContext\Resolver\Nette\NetteApplicationUIPresenterLatteContextResolver;
use Efabrica\PHPStanLatte\LatteTemplateResolver\AbstractClassTemplateResolver;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverResult;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\LayoutResolver\LayoutPathResolver;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\TemplateContext;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;
use function array_merge;
use function count;
use function dirname;
use function file_exists;
use function is_dir;
use function lcfirst;
use function preg_replace;
use function str_replace;
use function ucfirst;
use const DIRECTORY_SEPARATOR;

/**
 * @uses TemplateContext
 * @uses CollectedTemplateRender
 * @phpstan-type ActionDefinition array{templateContext: TemplateContext, line: int, renders: CollectedTemplateRender[], defaultTemplate: ?string, templatePaths: array<?string>, terminated: bool}
 */
final class NetteApplicationUIPresenter extends AbstractClassTemplateResolver
{
    public const CALL_SET_VIEW = 'Nette\Application\UI\Presenter::setView';

    private LayoutPathResolver $layoutPathResolver;

    public function __construct(LattePhpDocResolver $lattePhpDocResolver, ReflectionProvider $reflectionProvider, LayoutPathResolver $layoutPathResolver)
    {
        parent::__construct($lattePhpDocResolver, $reflectionProvider);
        $this->layoutPathResolver = $layoutPathResolver;
    }

    public function getSupportedClasses(): array
    {
        return ['Nette\Application\UI\Presenter'];
    }

    protected function getClassContextResolver(ClassReflection $classReflection, LatteContext $latteContext): LatteContextResolverInterface
    {
        return new NetteApplicationUIPresenterLatteContextResolver($classReflection, $latteContext);
    }

    protected function getClassResult(ClassReflection $classReflection, LatteContext $latteContext): LatteTemplateResolverResult
    {
        if ($classReflection->isAbstract() || $classReflection->isAnonymous()) {
            return new LatteTemplateResolverResult();
        }

        /** @var ActionDefinition[] $actions */
        $actions = [];

        // action methods - including matching render methods
        foreach ($this->getMethodsMatching($classReflection, '/^action.*/') as $methodReflection) {
            if (!$methodReflection->isPublic()) {
                continue;
            }
            $actionName = lcfirst((string)preg_replace('/^action/i', '', $methodReflection->getName()));

            if (!isset($actions[$actionName])) {
                $actions[$actionName] = $this->createActionDefinition($classReflection, $latteContext, $actionName);
            }
            $this->updateActionDefinitionByMethod($actions[$actionName], $classReflection, $methodReflection, $latteContext);

            // alternative renders (changed by setView in startup or action* method)
            $setViewCalls = array_merge(
                $latteContext->methodCallFinder()->findAllCalledOfType($classReflection->getName(), $methodReflection->getName(), self::CALL_SET_VIEW),
                $latteContext->methodCallFinder()->findAllCalledOfType($classReflection->getName(), 'startup', self::CALL_SET_VIEW)
            );
            foreach ($setViewCalls as $setViewCall) {
                $view = (string)$setViewCall->getParams()['view'];
                $actionViewName = $actionName . "($view)";
                $actions[$actionViewName] = $actions[$actionName];
                $actions[$actionViewName]['defaultTemplate'] = $this->findDefaultTemplateFilePath($classReflection, $view);
                $renderMethodName = 'render' . ucfirst($view);
                if ($classReflection->hasNativeMethod($renderMethodName)) {
                    $renderMethod = $classReflection->getNativeMethod($renderMethodName);
                    $this->updateActionDefinitionByMethod($actions[$actionViewName], $classReflection, $renderMethod, $latteContext);
                }
            }

            $alwaysSetViewCalls = array_merge(
                $latteContext->methodCallFinder()->findAllAlwaysCalledOfType($classReflection->getName(), $methodReflection->getName(), self::CALL_SET_VIEW),
                $latteContext->methodCallFinder()->findAllAlwaysCalledOfType($classReflection->getName(), 'startup', self::CALL_SET_VIEW)
            );

            if (count($alwaysSetViewCalls) === 0) {
                $renderMethodName = 'render' . ucfirst($actionName);
                if ($classReflection->hasNativeMethod($renderMethodName)) {
                    $renderMethod = $classReflection->getNativeMethod($renderMethodName);
                    $this->updateActionDefinitionByMethod($actions[$actionName], $classReflection, $renderMethod, $latteContext);
                }
            } else {
                unset($actions[$actionName]); // view is always changed
            }
        }

        // render methods without matching action method
        foreach ($this->getMethodsMatching($classReflection, '/^render.*/') as $methodReflection) {
            if (!$methodReflection->isPublic()) {
                continue;
            }
            $actionName = lcfirst((string)preg_replace('/^render/i', '', $methodReflection->getName()));

            if (!isset($actions[$actionName])) {
                $actions[$actionName] = $this->createActionDefinition($classReflection, $latteContext, $actionName);
            }
            $this->updateActionDefinitionByMethod($actions[$actionName], $classReflection, $methodReflection, $latteContext);
        }

        $result = new LatteTemplateResolverResult();
        foreach ($actions as $actionName => $actionDefinition) {
            // explicit render calls
            /** @var CollectedTemplateRender $templateRender */
            foreach ($actionDefinition['renders'] as $templateRender) {
                $result->addTemplateFromRender($templateRender, $actionDefinition['templateContext'], $classReflection->getName(), $actionName);

                $layoutFilePath = $this->layoutPathResolver->resolve($templateRender->getTemplatePath());
                if ($layoutFilePath !== null) {
                    $result->addTemplateFromRender($templateRender->withTemplatePath($layoutFilePath), $actionDefinition['templateContext'], $classReflection->getName(), $actionName);
                }
            }

            // default render with set template path
            foreach ($actionDefinition['templatePaths'] as $template) {
                if ($template === null) {
                    $result->addErrorFromBuilder(RuleErrorBuilder::message('Cannot automatically resolve latte template from expression.')
                        ->identifier('latte.cannotResolve')
                        ->file($classReflection->getFileName() ?? 'unknown')
                        ->line($actionDefinition['line']));
                    continue;
                }
                $result->addTemplate(new Template($template, $classReflection->getName(), $actionName, $actionDefinition['templateContext']));

                $layoutFilePath = $this->layoutPathResolver->resolve($template);
                if ($layoutFilePath !== null) {
                    $result->addTemplate(new Template($layoutFilePath, $classReflection->getName(), $actionName, $actionDefinition['templateContext']));
                }
            }

            // default render with default template
            if ($actionDefinition['defaultTemplate'] === null) {
                if (!$actionDefinition['terminated'] && $actionDefinition['templatePaths'] === []) { // might not be rendered at all (for example redirect or use set template path)
                    $result->addErrorFromBuilder(RuleErrorBuilder::message("Cannot resolve latte template for action $actionName")
                        ->identifier('latte.cannotResolve')
                        ->file($classReflection->getFileName() ?? 'unknown')
                        ->line($actionDefinition['line'])
                        ->identifier($actionName));
                }
                continue;
            }
            $result->addTemplate(new Template($actionDefinition['defaultTemplate'], $classReflection->getName(), $actionName, $actionDefinition['templateContext']));

            $layoutFilePath = $this->layoutPathResolver->resolve($actionDefinition['defaultTemplate']);
            if ($layoutFilePath !== null) {
                $result->addTemplate(new Template($layoutFilePath, $classReflection->getName(), $actionName, $actionDefinition['templateContext']));
            }
        }

        return $result;
    }

    /**
     * @phpstan-return ActionDefinition
     */
    private function createActionDefinition(ClassReflection $classReflection, LatteContext $latteContext, string $actionName): array
    {
        return [
            'templateContext' => $this->getClassGlobalTemplateContext($classReflection, $latteContext),
            'line' => -1,
            'renders' => [],
            'defaultTemplate' => $this->findDefaultTemplateFilePath($classReflection, $actionName),
            'templatePaths' => [],
            'terminated' => false,
        ];
    }

    /**
     * @phpstan-param ActionDefinition $actionDefinition
     */
    private function updateActionDefinitionByMethod(&$actionDefinition, ClassReflection $classReflection, MethodReflection $methodReflection, LatteContext $latteContext): void
    {
        $methodName = $methodReflection->getName();
        $actionDefinition['templateContext'] = $actionDefinition['templateContext']->union($latteContext->getMethodTemplateContext($classReflection->getName(), $methodName));
        $actionDefinition['line'] = $this->getMethodStartLine($classReflection, $methodName);
        $actionDefinition['renders'] = array_merge($actionDefinition['renders'], $latteContext->templateRenderFinder()->find($classReflection->getName(), $methodName));
        $actionDefinition['templatePaths'] = array_merge($actionDefinition['templatePaths'], $latteContext->templatePathFinder()->find($classReflection->getName(), $methodName));
        $actionDefinition['terminated'] = $actionDefinition['terminated'] || $latteContext->methodCallFinder()->hasAlwaysTerminatingCalls($classReflection->getName(), $methodName);
        $actionDefinition['terminated'] = $actionDefinition['terminated'] || $latteContext->methodFinder()->isAlwaysTerminated($classReflection->getName(), $methodName);
    }

    private function findDefaultTemplateFilePath(ClassReflection $classReflection, string $actionName): ?string
    {
        $shortClassName = $classReflection->getNativeReflection()->getShortName();
        $presenterName = str_replace('Presenter', '', $shortClassName);
        $dir = $this->getClassDir($classReflection);
        if ($dir === null) {
            return null;
        }

        $dir = is_dir($dir . DIRECTORY_SEPARATOR . 'templates') ? $dir : dirname($dir);

        $templateFileCandidates = [
            $dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $presenterName . DIRECTORY_SEPARATOR . $actionName . '.latte',
            $dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $presenterName . '.' . $actionName . '.latte',
        ];

        foreach ($templateFileCandidates as $templateFileCandidate) {
            if (file_exists($templateFileCandidate)) {
                return $templateFileCandidate;
            }
        }

        return null;
    }
}
