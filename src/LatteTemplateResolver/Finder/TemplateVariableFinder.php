<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver\Finder;

use Efabrica\PHPStanLatte\LatteTemplateResolver\Finder\NodeVisitor\TemplateVariableFinderNodeVisitor;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PHPStan\Analyser\Scope;

final class TemplateVariableFinder
{
    private TemplateTypeResolver $templateTypeResolver;

    public function __construct(TemplateTypeResolver $templateTypeResolver)
    {
        $this->templateTypeResolver = $templateTypeResolver;
    }

    /**
     * @return Variable[]
     */
    public function find(ClassMethod $classMethod, Scope $scope): array
    {
        $nodeTraverser = new NodeTraverser();

        $templateVariableFinderNodeVisitor = new TemplateVariableFinderNodeVisitor($scope, $this->templateTypeResolver, $this);
        $nodeTraverser->addVisitor($templateVariableFinderNodeVisitor);
        $nodeTraverser->traverse((array)$classMethod->stmts);

        return $templateVariableFinderNodeVisitor->getVariables();
    }
}
