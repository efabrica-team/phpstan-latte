<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplatePath;
use Efabrica\PHPStanLatte\LatteContext\Collector\TemplatePathCollector\TemplatePathCollectorInterface;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends AbstractLatteContextCollector<Node, CollectedTemplatePath>
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

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @phpstan-return null|CollectedTemplatePath[]
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

        $paths = [];
        foreach ($this->templatePathCollectors as $templatePathCollector) {
            if (!$templatePathCollector->isSupported($node)) {
                continue;
            }
            $paths = array_merge($paths, $templatePathCollector->collect($node, $scope));
        }

        $actualClassName = $classReflection->getName();
        if ($paths === []) {
            // failed to resolve
            return [new CollectedTemplatePath($actualClassName, $functionName, null)];
        }
        $templatePaths = [];
        foreach ($paths as $path) {
            $templatePaths[] = new CollectedTemplatePath($actualClassName, $functionName, $path);
        }
        return $templatePaths;
    }
}
