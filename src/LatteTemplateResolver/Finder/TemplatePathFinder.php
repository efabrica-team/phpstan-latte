<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver\Finder;

use Efabrica\PHPStanLatte\LatteTemplateResolver\Finder\NodeVisitor\TemplatePathFinderNodeVisitor;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\NodeTraverser;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;

final class TemplatePathFinder
{
    private TemplateTypeResolver $templateTypeResolver;

    private ValueResolver $valueResolver;

    public function __construct(TemplateTypeResolver $templateTypeResolver, ValueResolver $valueResolver)
    {
        $this->templateTypeResolver = $templateTypeResolver;
        $this->valueResolver = $valueResolver;
    }

    public function find(ClassMethod $classMethod, Scope $scope): ?string
    {
        $nodeTraverser = new NodeTraverser();

        $templatePathFinderNodeVisitor = new TemplatePathFinderNodeVisitor($scope, $this->templateTypeResolver, $this->valueResolver);
        $nodeTraverser->addVisitor($templatePathFinderNodeVisitor);
        $nodeTraverser->traverse((array)$classMethod->stmts);

        return $templatePathFinderNodeVisitor->getPath();
    }
}
