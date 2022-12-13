<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\TemplateRenderCollector;
use Efabrica\PHPStanLatte\Collector\TemplateRenderMethodPhpDocCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedTemplateRender;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Node\CollectedDataNode;

/**
 * @phpstan-import-type CollectedTemplateRenderArray from CollectedTemplateRender
 */
final class TemplateRenderFinder
{
    /**
     * @var array<string, array<string, CollectedTemplateRender[]>>
     */
    private array $collectedTemplateRenders = [];

    private MethodCallFinder $methodCallFinder;

    private TemplatePathFinder $templatePathFinder;

    private PathResolver $pathResolver;

    public function __construct(CollectedDataNode $collectedDataNode, TypeSerializer $typeSerializer, MethodCallFinder $methodCallFinder, TemplatePathFinder $templatePathFinder, PathResolver $pathResolver)
    {
        $this->methodCallFinder = $methodCallFinder;
        $this->templatePathFinder = $templatePathFinder;
        $this->pathResolver = $pathResolver;

        $collectedTemplateRenders = array_merge(
            TemplateRenderCollector::loadData($collectedDataNode, $typeSerializer, CollectedTemplateRender::class),
            TemplateRenderMethodPhpDocCollector::loadData($collectedDataNode, $typeSerializer, CollectedTemplateRender::class)
        );
        foreach ($collectedTemplateRenders as $collectedTemplateRender) {
            $className = $collectedTemplateRender->getClassName();
            $methodName = $collectedTemplateRender->getMethodName();
            if (!isset($this->collectedTemplateRenders[$className][$methodName])) {
                $this->collectedTemplateRenders[$className][$methodName] = [];
            }
            $templatePath = $collectedTemplateRender->getTemplatePath();
            if ($templatePath === false) {
                $this->collectedTemplateRenders[$className][$methodName][] = $collectedTemplateRender;
            } else {
                $templatePaths = $this->pathResolver->expand($templatePath);
                if ($templatePaths === null) {
                    $this->collectedTemplateRenders[$className][$methodName][] = $collectedTemplateRender->withError();
                } else {
                    foreach ($templatePaths as $templatePath) {
                        $this->collectedTemplateRenders[$className][$methodName][] = $collectedTemplateRender->withTemplatePath($templatePath);
                    }
                }
            }
        }
    }

    /**
     * @return CollectedTemplateRender[]
     */
    public function find(string $className, string $methodName): array
    {
        $templateRenders = $this->findInMethodCalls($className, $methodName);

        $defaultTemplatePaths = $this->templatePathFinder->find($className, $methodName);

        $templateRendersWithTemplatePaths = [];
        foreach ($templateRenders as $templateRender) {
            // when render call does not specify template directly use default template(s) collected from setFile() calls
            if ($templateRender->getTemplatePath() === null) {
                foreach ($defaultTemplatePaths as $defaultTemplatePath) {
                    $templateRendersWithTemplatePaths[] = $templateRender->withTemplatePath($defaultTemplatePath);
                }
            } else {
                $templateRendersWithTemplatePaths[] = $templateRender;
            }
        }

        return $templateRendersWithTemplatePaths;
    }

    /**
     * @return CollectedTemplateRender[]
     */
    public function findByMethod(ReflectionMethod $method): array
    {
        return $this->find($method->getDeclaringClass()->getName(), $method->getName());
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     * @return CollectedTemplateRender[]
     */
    private function findInMethodCalls(string $className, string $methodName, array &$alreadyFound = []): array
    {
        if (isset($alreadyFound[$className][$methodName])) {
            return []; // stop recursion
        } else {
            $alreadyFound[$className][$methodName] = true;
        }

        $collectedTemplateRenders = [
            $this->collectedTemplateRenders[$className][$methodName] ?? [],
        ];

        $methodCalls = $this->methodCallFinder->findCalled($className, $methodName);
        foreach ($methodCalls as $calledClassName => $calledMethods) {
            foreach ($calledMethods as $calledMethod) {
                $collectedTemplateRenders[] = $this->findInMethodCalls($calledClassName, $calledMethod, $alreadyFound);
            }
        }

        return array_merge(...$collectedTemplateRenders);
    }
}
