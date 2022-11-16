<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\LatteTemplateResolver\Finder\ComponentsFinder;
use Efabrica\PHPStanLatte\LatteTemplateResolver\Finder\TemplateVariableFinder;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
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

    /**
     * @param InClassNode $node
     */
    public function findTemplatesWithParameters(Node $node, Scope $scope): array
    {
        if ($scope->getClassReflection() === null) {
            return [];
        }

        /** @var Class_ $class */
        $class = $node->getOriginalNode();
        $shortClassName = (string)$class->name;
        $methods = $class->getMethods();

        $classVariables = [];
        $classReflection = $scope->getClassReflection();
        if ($classReflection instanceof ClassReflection) {
            $objectType = new ObjectType($classReflection->getName());
            $classVariables[] = new Variable('actualClass', $objectType);
            $classVariables[] = new Variable('presenter', $objectType);
        }
        $startupVariables = [];
        $actionsWithVariables = [];
        foreach ($methods as $method) {
            $methodName = (string)$method->name;
            if ($methodName === 'startup') {
                $startupVariables = $this->templateVariableFinder->find($method, $scope, $scope->getClassReflection());
            }

            if (!str_starts_with($methodName, 'render') && !str_starts_with($methodName, 'action')) {
                continue;
            }

            $actionName = lcfirst(str_replace(['action', 'render'], '', $methodName));
            if (!isset($actionsWithVariables[$actionName])) {
                $actionsWithVariables[$actionName] = [];
            }

            $actionsWithVariables[$actionName] = array_merge($actionsWithVariables[$actionName], $this->templateVariableFinder->find($method, $scope, $scope->getClassReflection()));
        }

        $templates = [];
        foreach ($actionsWithVariables as $actionName => $actionVariables) {
            $template = $this->findTemplateFilePath($shortClassName, $actionName, $scope);
            if ($template === null) {
                continue;
            }
            $variables = array_merge($startupVariables, $classVariables, $actionVariables);
            $templates[] = new Template($template, $variables);
        }

        return $templates;
    }

    /**
     * @param InClassNode $node
     */
    public function findComponents(Node $node, Scope $scope): array
    {
        /** @var Class_ $class */
        $class = $node->getOriginalNode();
        return $this->componentsFinder->find($class, $scope);
    }

    private function findTemplateFilePath(string $shortClassName, string $actionName, Scope $scope): ?string
    {
        $presenterName = str_replace('Presenter', '', $shortClassName);
        $dir = dirname($scope->getFile());
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
