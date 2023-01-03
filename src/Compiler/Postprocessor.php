<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\AddTypeToComponentNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\AddVarTypesNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FormsNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\ChangeFiltersNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\NodeVisitorStorage;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\VariableCollector\DynamicFilterVariables;
use Efabrica\PHPStanLatte\VariableCollector\VariableCollectorStorage;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

final class Postprocessor
{
    private NodeVisitorStorage $nodeVisitorStorage;

    private Standard $printerStandard;

    private TypeToPhpDoc $typeToPhpDoc;

    private DynamicFilterVariables $dynamicFilterVariables;

    private VariableCollectorStorage $variableCollectorStorage;

    public function __construct(
        NodeVisitorStorage $nodeVisitorStorage,
        Standard $printerStandard,
        TypeToPhpDoc $typeToPhpDoc,
        DynamicFilterVariables $dynamicFilterVariables,
        VariableCollectorStorage $variableCollectorStorage
    ) {
        $this->nodeVisitorStorage = $nodeVisitorStorage;
        $this->printerStandard = $printerStandard;
        $this->typeToPhpDoc = $typeToPhpDoc;
        $this->dynamicFilterVariables = $dynamicFilterVariables;
        $this->variableCollectorStorage = $variableCollectorStorage;
    }

    public function postProcess(string $phpContent, Template $template): string
    {
        $filters = [];
        foreach ($template->getFilters() as $filter) {
            $filters[$filter->getName()] = $filter->getTypeAsString();
        }

        $this->dynamicFilterVariables->addFilters($filters);

        $addVarTypeFromCollectorStorageNodeVisitor = new AddVarTypesNodeVisitor($this->variableCollectorStorage->collectVariables(), $this->typeToPhpDoc);
        $this->nodeVisitorStorage->addTemporaryNodeVisitor(100, $addVarTypeFromCollectorStorageNodeVisitor);

        $addVarTypeNodeVisitor = new AddVarTypesNodeVisitor($template->getVariables(), $this->typeToPhpDoc);
        $this->nodeVisitorStorage->addTemporaryNodeVisitor(100, $addVarTypeNodeVisitor);

        $addTypeToComponentNodeVisitor = new AddTypeToComponentNodeVisitor($template->getComponents(), $this->typeToPhpDoc);
        $this->nodeVisitorStorage->addTemporaryNodeVisitor(100, $addTypeToComponentNodeVisitor);

        $changeFilterNodeVisitor = new ChangeFiltersNodeVisitor($filters);
        $this->nodeVisitorStorage->addTemporaryNodeVisitor(200, $changeFilterNodeVisitor);

        foreach ($this->nodeVisitorStorage->getNodeVisitors() as $nodeVisitors) {
            $phpContent = $this->processNodeVisitors($phpContent, $nodeVisitors, $template);
        }

        $this->nodeVisitorStorage->resetTemporaryNodeVisitors();
        return $phpContent;
    }

    /**
     * @param NodeVisitor[] $nodeVisitors
     */
    private function processNodeVisitors(string $phpContent, array $nodeVisitors, Template $template): string
    {
        $phpStmts = $this->findNodes($phpContent);
        $nodeTraverser = new NodeTraverser();
        foreach ($nodeVisitors as $nodeVisitor) {
            $this->setupVisitor($nodeVisitor, $template);
            $nodeTraverser->addVisitor($nodeVisitor);
        }

        $newPhpStmts = $nodeTraverser->traverse($phpStmts);
        return $this->printerStandard->prettyPrintFile($newPhpStmts);
    }

    private function setupVisitor(NodeVisitor $nodeVisitor, Template $template): void
    {
        if ($nodeVisitor instanceof ActualClassNodeVisitorInterface) {
            $nodeVisitor->setActualClass($template->getActualClass());
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
        $parserFactory = new ParserFactory();
        $phpParser = $parserFactory->create(ParserFactory::PREFER_PHP7);
        $phpStmts = (array)$phpParser->parse($phpContent);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ParentConnectingVisitor());
        $nodeTraverser->addVisitor(new NameResolver());
        $newPhpStmts = $nodeTraverser->traverse($phpStmts);

        return $newPhpStmts;
    }
}
