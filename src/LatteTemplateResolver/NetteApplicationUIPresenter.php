<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Collector\Finder\MethodCallFinder;
use Efabrica\PHPStanLatte\Collector\Finder\VariableFinder;
use Efabrica\PHPStanLatte\Collector\VariableCollector;
use Efabrica\PHPStanLatte\Collector\Finder\ComponentFinder;
use Efabrica\PHPStanLatte\LatteTemplateResolver\Finder\ComponentsFinder;
use Efabrica\PHPStanLatte\LatteTemplateResolver\Finder\TemplateVariableFinder;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Node\InClassNode;
use PHPStan\Type\ObjectType;

final class NetteApplicationUIPresenter implements LatteTemplateResolverInterface
{
    private TemplateVariableFinder $templateVariableFinder;

    private ComponentsFinder $componentsFinder;

    public function __construct(
        TemplateVariableFinder $templateVariableFinder,
        ComponentsFinder $componentsFinder
    ) {
        $this->templateVariableFinder = $templateVariableFinder;
        $this->componentsFinder = $componentsFinder;
    }

    public function check(Node $node, Scope $scope): bool
    {
        if (!$node instanceof InClassNode) {
            return false;
        }

        $class = $node->getOriginalNode();
        if (!$class instanceof Class_) {
            return false;
        }

        $className = (string)$class->namespacedName;
        if (!$className) {
            return false;
        }

        $objectType = new ObjectType($className);
        return $objectType->isInstanceOf('Nette\Application\UI\Presenter')
            ->yes();
    }

    public function findTemplates(string $className, CollectedDataNode $collectedDataNode): array
    {
        $methodCallFinder = new MethodCallFinder($collectedDataNode);
        $variableFinder = new VariableFinder($collectedDataNode, $methodCallFinder);
        $componentFinder = new ComponentFinder($collectedDataNode);

        $reflectionClass = (new BetterReflection())->reflector()->reflectClass($className);
        $reflectionMethods = $reflectionClass->getMethods();

        $classVariables = [];
        $presenterType = new ObjectType($className);
        $classVariables[] = new Variable('actualClass', $presenterType);
        $classVariables[] = new Variable('presenter', $presenterType);

        $startupVariables = [];
        $startupComponents = [];
        $actionsWithVariables = [];
        $actionsWithComponents = [];

        foreach ($reflectionMethods as $reflectionMethod) {
            $declaringClassName = $reflectionMethod->getDeclaringClass()->getName();
            $methodName = $reflectionMethod->getName();

            if ($methodName === 'startup') {
                $startupVariables = $variableFinder->find($declaringClassName, $methodName);
                $startupComponents = $componentFinder->find($declaringClassName, $methodName);
            }

            if (!str_starts_with($methodName, 'render') && !str_starts_with($methodName, 'action')) {
                continue;
            }

            $actionName = lcfirst(str_replace(['action', 'render'], '', $methodName));
            if (!isset($actionsWithVariables[$actionName])) {
                $actionsWithVariables[$actionName] = [];
            }
            $actionsWithVariables[$actionName] = array_merge($actionsWithVariables[$actionName], $variableFinder->find($declaringClassName, $methodName));

            if (!isset($actionsWithComponents[$actionName])) {
                $actionsWithComponents[$actionName] = [];
            }
            $actionsWithComponents[$actionName] = array_merge($actionsWithComponents[$actionName], $componentFinder->find($declaringClassName, $methodName));
        }

        $shortClassName = $reflectionClass->getShortName();
        $dir = dirname($reflectionClass->getFileName());

        $globalComponents = $componentFinder->find($className, '');

        $templates = [];
        foreach ($actionsWithVariables as $actionName => $actionVariables) {
            $template = $this->findTemplateFilePath($shortClassName, $actionName, $dir);
            if ($template === null) {
                continue;
            }
            $variables = array_merge($startupVariables, $classVariables, $actionVariables);
            $components = array_merge($globalComponents, $startupComponents, $actionsWithComponents[$actionName] ?? []);
            $templates[] = new Template($template, $variables, $components);
        }

        return $templates;
    }

    private function findTemplateFilePath(string $shortClassName, string $actionName, string $dir): ?string
    {
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
