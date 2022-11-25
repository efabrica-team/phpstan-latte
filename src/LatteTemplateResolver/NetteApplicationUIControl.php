<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Node\InClassNode;
use PHPStan\Type\ObjectType;

final class NetteApplicationUIControl extends AbstractTemplateResolver
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
        return $objectType->isInstanceOf('Nette\Application\UI\Control')->yes();
    }

    protected function getTemplates(string $className, CollectedDataNode $collectedDataNode): array
    {
        $reflectionClass = (new BetterReflection())->reflector()->reflectClass($className);
        $reflectionMethod = $reflectionClass->getMethod('render');

        // TODO check all render* methods

        if ($reflectionMethod === null) {
            return [];
        }
        $declaringClassName = $reflectionMethod->getDeclaringClass()->getName();
        $variables = $this->variableFinder->find($declaringClassName, $reflectionMethod->getName());
        $objectType = new ObjectType($className);
        $variables[] = new Variable('actualClass', $objectType);
        $variables[] = new Variable('control', $objectType);

        $globalComponents = $this->componentFinder->find($className, '');
        $methodComponents = $this->componentFinder->find($declaringClassName, $reflectionMethod->getName());
        $components = array_merge($globalComponents, $methodComponents);
        $templatePaths = $this->templatePathFinder->find($declaringClassName, $reflectionMethod->getName());

        $templates = [];
        foreach ($templatePaths as $templatePath) {
            $templates[] = new Template($templatePath, $variables, $components);
        }
        return $templates;
    }
}
