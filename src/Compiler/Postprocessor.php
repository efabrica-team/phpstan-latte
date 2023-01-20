<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

use Efabrica\PHPStanLatte\Compiler\Compiler\CompilerInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ComponentsNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FiltersNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FormsNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\VariablesNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\NodeVisitorStorage;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\VariableCollector\DynamicFilterVariables;
use Efabrica\PHPStanLatte\VariableCollector\VariableCollectorStorage;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Parser\Parser;

final class Postprocessor
{
    private Parser $parser;

    private NodeVisitorStorage $nodeVisitorStorage;

    private Standard $printerStandard;

    private DynamicFilterVariables $dynamicFilterVariables;

    private VariableCollectorStorage $variableCollectorStorage;

    private CompilerInterface $compiler;

    public function __construct(
        Parser $parser,
        NodeVisitorStorage $nodeVisitorStorage,
        Standard $printerStandard,
        DynamicFilterVariables $dynamicFilterVariables,
        VariableCollectorStorage $variableCollectorStorage,
        CompilerInterface $compiler
    ) {
        $this->parser = $parser;
        $this->nodeVisitorStorage = $nodeVisitorStorage;
        $this->printerStandard = $printerStandard;
        $this->dynamicFilterVariables = $dynamicFilterVariables;
        $this->variableCollectorStorage = $variableCollectorStorage;
        $this->compiler = $compiler;
    }

    public function postProcess(string $phpContent, Template $template): string
    {
        $filters = [];
        foreach ($template->getFilters() as $filter) {
            $filters[$filter->getName()] = $filter->getTypeAsString();
        }
        $this->dynamicFilterVariables->addFilters($filters);

        $phpStmts = $this->findNodes($phpContent);
        foreach ($this->nodeVisitorStorage->getNodeVisitors() as $nodeVisitors) {
            $phpStmts = $this->processNodeVisitors($phpStmts, $nodeVisitors, $template);
        }

        return $this->printerStandard->prettyPrintFile($phpStmts);
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
            $nodeVisitor->setVariables(array_merge($template->getVariables(), $this->variableCollectorStorage->collectVariables()));
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
        if ($nodeVisitor instanceof FormsNodeVisitorInterface) {
            $nodeVisitor->setForms($template->getForms());
        }
    }

    /**
     * @return Node[]
     */
    private function findNodes(string $phpContent): array
    {
        return (array)$this->parser->parseString($phpContent);
    }
}
