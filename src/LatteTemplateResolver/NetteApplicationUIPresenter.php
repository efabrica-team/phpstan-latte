<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\Variable as TemplateVariable;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Type\ObjectType;

final class NetteApplicationUIPresenter implements LatteTemplateResolverInterface
{
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

        $templates = [];
        foreach ($methods as $method) {
            $methodName = (string)$method->name;

            if (!str_starts_with($methodName, 'render') && !str_starts_with($methodName, 'action')) {
                continue;
            }

            $template = $this->findTemplateFilePath($shortClassName, $methodName, $scope);
            if ($template === null) {
                continue;
            }
            $templates[] = new Template($template, $this->findVariables($method, $scope));
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

    private function findTemplateFilePath(string $shortClassName, string $methodName, Scope $scope): ?string
    {
        $presenterName = str_replace('Presenter', '', $shortClassName);

        $actionName = str_replace(['action', 'render'], '', $methodName);
        $actionName = lcfirst($actionName);

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

    /**
     * @return TemplateVariable[]
     */
    private function findVariables(ClassMethod $classMethod, Scope $scope): array
    {
        $nodeTraverser = new NodeTraverser();

        // TODO create NodeVisitor class
        $templateVariableFinder = new class($scope) extends NodeVisitorAbstract
        {
            private Scope $scope;

            /** @var TemplateVariable[] */
            private array $variables = [];

            public function __construct(Scope $scope)
            {
                $this->scope = $scope;
            }

            public function enterNode(Node $node): ?Node
            {
                if (!$node instanceof Assign) {
                    return null;
                }

                if ($node->var instanceof Variable) {
                    $var = $node->var;
                    $nameNode = $node->var->name;
                } elseif ($node->var instanceof PropertyFetch) {
                    $var = $node->var->var;
                    $nameNode = $node->var->name;
                } else {
                    return null;
                }

                if ($nameNode instanceof Expr) {
                    return null;
                }

                if (!$this->scope->getType($var)->accepts(new ObjectType('Nette\Application\UI\Template'), true)->yes()) {
                    return null;
                }

                $variableName = is_string($nameNode) ? $nameNode : $nameNode->name;
                $this->variables[] = new TemplateVariable($variableName, $this->scope->getType($node->expr));
                return null;
            }

            /**
             * @return TemplateVariable[]
             */
            public function getVariables(): array
            {
                return $this->variables;
            }
        };

        $nodeTraverser->addVisitor($templateVariableFinder);
        $nodeTraverser->traverse((array)$classMethod->stmts);

        return $templateVariableFinder->getVariables();
    }
}
