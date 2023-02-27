<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

use Efabrica\PHPStanLatte\Compiler\Compiler\CompilerInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ComponentsNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FiltersNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FormsNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FunctionsNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\VariablesNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\NodeVisitorStorage;
use Efabrica\PHPStanLatte\Template\Template;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Parser\Parser;

final class Postprocessor
{
    private Parser $parser;

    private NodeVisitorStorage $nodeVisitorStorage;

    private Standard $printerStandard;

    private CompilerInterface $compiler;

    private ScopeFactory $scopeFactory;

    private NodeScopeResolver $nodeScopeResolver;

    public function __construct(
        Parser $parser,
        NodeVisitorStorage $nodeVisitorStorage,
        Standard $printerStandard,
        CompilerInterface $compiler,
        ScopeFactory $scopeFactory,
        NodeScopeResolver $nodeScopeResolver
    ) {
        $this->parser = $parser;
        $this->nodeVisitorStorage = $nodeVisitorStorage;
        $this->printerStandard = $printerStandard;
        $this->compiler = $compiler;
        $this->scopeFactory = $scopeFactory;
        $this->nodeScopeResolver = $nodeScopeResolver;
    }

    public function postProcess(string $phpContent, Template $template, string $compileFilePath): string
    {
        $phpStmts = $this->findNodes($phpContent);
        foreach ($this->nodeVisitorStorage->getNodeVisitors() as $nodeVisitors) {
            $phpStmts = $this->processNodeVisitors($phpStmts, $nodeVisitors, $template);
        }

        $phpContent = $this->printerStandard->prettyPrintFile($phpStmts);
        file_put_contents($compileFilePath, $phpContent);
        $realPath = realpath($compileFilePath) ?: '';
        if ($realPath === '') {
            return '';
        }
        require($compileFilePath); // load type definitions from compiled template

        $scope = $this->scopeFactory->create(ScopeContext::create($realPath));

        $phpStmts = $this->findNodes($phpContent);

        $nodeScopeResolver = clone $this->nodeScopeResolver;
        $nodeScopeResolver->processNodes($phpStmts, $scope, function (Node $node, Scope $scope) {
            if ($node instanceof Expr && !$node instanceof EncapsedStringPart) {
                $node->setAttribute(ExprTypeNodeVisitorInterface::TYPE_ATTRIBUTE, $scope->getType($node));
            }
        });

        foreach ($this->nodeVisitorStorage->getNodeVisitors(true) as $nodeVisitors) {
            $phpStmts = $this->processNodeVisitors($phpStmts, $nodeVisitors, $template);
        }
        $phpContent = $this->printerStandard->prettyPrintFile($phpStmts);

        // update contents
        file_put_contents($compileFilePath, $phpContent);

        return $realPath;
    }

    /**
     * @param Node[] $phpStmts
     * @param NodeVisitor[] $nodeVisitors
     * @return Node[]
     */
    private function processNodeVisitors(array $phpStmts, array $nodeVisitors, Template $template): array
    {
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ParentConnectingVisitor()); // symplify/phpstan-rules compatibility
        foreach ($nodeVisitors as $cleanNodeVisitor) {
            $nodeVisitor = clone $cleanNodeVisitor;
            $this->setupVisitor($nodeVisitor, $template);
            $nodeTraverser->addVisitor($nodeVisitor);
        }

        return $nodeTraverser->traverse($phpStmts);
    }

    private function setupVisitor(NodeVisitor $nodeVisitor, Template $template): void
    {
        if ($nodeVisitor instanceof ActualClassNodeVisitorInterface) {
            $nodeVisitor->setActualClass($template->getActualClass());
        }
        if ($nodeVisitor instanceof VariablesNodeVisitorInterface) {
            $nodeVisitor->setVariables($template->getVariables());
        }
        if ($nodeVisitor instanceof ComponentsNodeVisitorInterface) {
            $nodeVisitor->setComponents($template->getComponents());
        }
        if ($nodeVisitor instanceof FiltersNodeVisitorInterface) {
            $filters = [];
            foreach ($template->getFilters() as $filter) {
                $filters[$filter->getName()] = $filter->getTypeAsString();
            }
            $filters = array_merge($filters, $this->compiler->getFilters());
            $filters = LatteVersion::isLatte2() ? array_change_key_case($filters) : $filters;
            $nodeVisitor->setFilters($filters);
        }
        if ($nodeVisitor instanceof FunctionsNodeVisitorInterface) {
            $nodeVisitor->setFunctions($this->compiler->getFunctions());
        }
        if ($nodeVisitor instanceof FormsNodeVisitorInterface) {
            $nodeVisitor->setForms($template->getForms());
        }
    }

    /**
     * @return Node[]
     */
    private function findNodes(string $phpContent): array
    {
        return $this->parser->parseString($phpContent);
    }
}
