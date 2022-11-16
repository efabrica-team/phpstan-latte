<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\LatteTemplateResolver\Finder\ComponentsFinder;
use Efabrica\PHPStanLatte\LatteTemplateResolver\Finder\TemplatePathFinder;
use Efabrica\PHPStanLatte\LatteTemplateResolver\Finder\TemplateVariableFinder;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;

final class NetteApplicationUIControl implements LatteTemplateResolverInterface
{
    private TemplateVariableFinder $templateVariableFinder;

    private TemplatePathFinder $templatePathFinder;

    private ComponentsFinder $componentsFinder;

    public function __construct(
        TemplateVariableFinder $templateVariableFinder,
        TemplatePathFinder $templatePathFinder,
        ComponentsFinder $componentsFinder
    ) {
        $this->templateVariableFinder = $templateVariableFinder;
        $this->templatePathFinder = $templatePathFinder;
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
        return $objectType->isInstanceOf('Nette\Application\UI\Control')->yes();
    }

    /**
     * @param InClassNode $node
     */
    public function findTemplatesWithParameters(Node $node, Scope $scope): array
    {
        /** @var Class_ $class */
        $class = $node->getOriginalNode();
        $method = $class->getMethod('render');

        if ($method === null) {
            return [];
        }

        $variables = $this->templateVariableFinder->find($method, $scope, $scope->getClassReflection());
        $template = $this->templatePathFinder->find($method, $scope);
        if ($template === null) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection instanceof ClassReflection) {
            $objectType = new ObjectType($classReflection->getName());
            $variables[] = new Variable('actualClass', $objectType);
            $variables[] = new Variable('control', $objectType);
        }

        return [
            new Template($template, $variables),
        ];
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
}
