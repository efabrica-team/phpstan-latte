<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedResolvedNode;
use Efabrica\PHPStanLatte\Template\Template;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Node\InClassNode;
use PHPStan\Type\ObjectType;

abstract class AbstractClassTemplateResolver extends AbstractTemplateResolver
{
    private const PARAM_CLASS_NAME = 'className';

    public function collect(Node $node, Scope $scope): ?CollectedResolvedNode
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        if (!$node instanceof InClassNode) {
            return null;
        }

        $class = $node->getOriginalNode();
        if (!$class instanceof Class_) {
            return null;
        }

        $className = (string)$class->namespacedName;
        if (!$className) {
            return null;
        }

        $objectType = new ObjectType($className);
        foreach ($this->getSupportedClasses() as $supportedClass) {
            if ($objectType->isInstanceOf($supportedClass)->yes()) {
                return new CollectedResolvedNode(static::class, [self::PARAM_CLASS_NAME => $className]);
            }
        }

        return null;
    }

    /**
     * @return Template[]
     */
    protected function getTemplates(CollectedResolvedNode $resolvedNode, CollectedDataNode $collectedDataNode): array
    {
        $className = $resolvedNode->getParam(self::PARAM_CLASS_NAME);
        $reflectionClass = (new BetterReflection())->reflector()->reflectClass($className);

        $fileName = $reflectionClass->getFileName();
        if ($fileName === null) {
            return [];
        }

        return $this->getClassTemplates($reflectionClass, $collectedDataNode);
    }

    /**
     * @return ReflectionMethod[]
     */
    protected function getMethodsMatching(ReflectionClass $reflectionClass, string $pattern): array
    {
        $methods = [];
        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            if (preg_match($pattern, $reflectionMethod->getName()) === 1) {
                $methods[] = $reflectionMethod;
            }
        }
        return $methods;
    }

    /**
     * @return class-string[]
     */
    abstract protected function getSupportedClasses(): array;

    /**
     * @return Template[]
     */
    abstract protected function getClassTemplates(ReflectionClass $resolveClass, CollectedDataNode $collectedDataNode): array;
}
