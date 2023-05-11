<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedError;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplatePath;
use Efabrica\PHPStanLatte\LatteContext\Collector\TemplatePathCollector\TemplatePathCollectorInterface;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends AbstractLatteContextCollector<CollectedTemplatePath|CollectedError>
 */
final class TemplatePathCollector extends AbstractLatteContextCollector
{
    /** @var TemplatePathCollectorInterface[] */
    private array $templatePathCollectors;

    private LattePhpDocResolver $lattePhpDocResolver;

    /**
     * @param TemplatePathCollectorInterface[] $templatePathCollectors
     */
    public function __construct(
        array $templatePathCollectors,
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->templatePathCollectors = $templatePathCollectors;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeTypes(): array
    {
        $nodeTypes = [];
        foreach ($this->templatePathCollectors as $collector) {
            $nodeTypes = array_merge($nodeTypes, $collector->getNodeTypes());
        }
        return array_unique($nodeTypes);
    }

    /**
     * @phpstan-return null|array<CollectedTemplatePath|CollectedError>
     */
    public function collectData(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $functionName = $scope->getFunctionName();
        if ($functionName === null) {
            return null;
        }

        if ($this->lattePhpDocResolver->resolveForNode($node, $scope)->isIgnored()) {
            return null;
        }

        $paths = null;
        $templatePaths = [];
        $isCollected = false;
        foreach ($this->templatePathCollectors as $templatePathCollector) {
            if (!$templatePathCollector->isSupported($node)) {
                continue;
            }
            $isCollected = true;
            $collectedPaths = $templatePathCollector->collect($node, $scope);
            if ($collectedPaths === null) {
                continue;
            }
            $paths = $paths ?? [];
            foreach ($collectedPaths as $collectedPath) {
                if ($collectedPath instanceof CollectedError) {
                    $templatePaths[] = $collectedPath;
                    continue;
                }
                $paths[] = $collectedPath;
            }
        }

        if ($isCollected === false) {
            return null;
        }

        $actualClassName = $classReflection->getName();
        if ($paths === null) {
            // failed to resolve
            return [CollectedError::build($node, $scope, 'Cannot automatically resolve latte template from expression.')];
        }
        $paths = array_unique($paths);
        foreach ($paths as $path) {
            $templatePaths[] = new CollectedTemplatePath($actualClassName, $functionName, $path);
        }
        return $templatePaths;
    }
}
