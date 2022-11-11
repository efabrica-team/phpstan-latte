<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\LatteTemplateResolver\Finder\TemplateVariableFinder;
use Efabrica\PHPStanLatte\Template\Template;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Type\ObjectType;

final class NetteApplicationUIPresenter implements LatteTemplateResolverInterface
{
    private TemplateVariableFinder $templateVariableFinder;

    public function __construct(TemplateVariableFinder $templateVariableFinder)
    {
        $this->templateVariableFinder = $templateVariableFinder;
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
        /** @var Class_ $class */
        $class = $node->getOriginalNode();
        $shortClassName = (string)$class->name;
        $methods = $class->getMethods();

        $startupVariables = [];
        $actionsWithVariables = [];
        foreach ($methods as $method) {
            $methodName = (string)$method->name;

            if ($methodName === 'startup') {
                $startupVariables = $this->templateVariableFinder->find($method, $scope);
            }

            if (!str_starts_with($methodName, 'render') && !str_starts_with($methodName, 'action')) {
                continue;
            }

            $actionName = lcfirst(str_replace(['action', 'render'], '', $methodName));
            if (!isset($actionsWithVariables[$actionName])) {
                $actionsWithVariables[$actionName] = [];
            }
            $actionsWithVariables[$actionName] = array_merge($actionsWithVariables[$actionName], $this->templateVariableFinder->find($method, $scope));
        }

        $templates = [];
        foreach ($actionsWithVariables as $actionName => $actionVariables) {
            $template = $this->findTemplateFilePath($shortClassName, $actionName, $scope);
            if ($template === null) {
                continue;
            }
            $variables = array_merge($startupVariables, $actionVariables);
            $templates[] = new Template($template, $variables);
        }

        return $templates;
    }

    /**
     * @param InClassNode $node
     */
    public function findComponents(Node $node, Scope $scope): array
    {
        return [];
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
