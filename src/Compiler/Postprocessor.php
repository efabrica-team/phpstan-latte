<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\AddFormClassesNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\AddTypeToComponentNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\AddVarTypesNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\ChangeFiltersNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\NodeVisitorStorage;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedForm;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\VariableCollector\DynamicFilterVariables;
use Efabrica\PHPStanLatte\VariableCollector\VariableCollectorStorage;
use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\Builder\Param;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable as NodeVariable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Node\FileNode;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\Type\VerbosityLevel;
use Throwable;

final class Postprocessor
{
    private Parser $parser;

    private NodeVisitorStorage $nodeVisitorStorage;

    private Standard $printerStandard;

    private TypeToPhpDoc $typeToPhpDoc;

    private DynamicFilterVariables $dynamicFilterVariables;

    private VariableCollectorStorage $variableCollectorStorage;

    public function __construct(
        Parser $parser,
        NodeVisitorStorage $nodeVisitorStorage,
        Standard $printerStandard,
        TypeToPhpDoc $typeToPhpDoc,
        DynamicFilterVariables $dynamicFilterVariables,
        VariableCollectorStorage $variableCollectorStorage
    ) {
        $this->parser = $parser;
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

        $addFormClassesNodeVisitor = new AddFormClassesNodeVisitor($template->getForms());
        $this->nodeVisitorStorage->addTemporaryNodeVisitor(300, $addFormClassesNodeVisitor);

        foreach ($this->nodeVisitorStorage->getNodeVisitors() as $priority => $nodeVisitors) {
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
    }

    /**
     * @param CollectedForm[] $forms
     */
    private function addFormClasses(string $phpContent, array $forms): string
    {

                // TODO select corresponding part of code and replace all occurences in it, then replace original code with new

        for ($i = 0; $i < 5; $i++) {    // label and input etc.
            // TODO node visitor
            /** @var string $phpContent */
            $phpContent = preg_replace('/new ' . $className . '(.*?)end\(\$this->global->formsStack\)\[[\'"]' . $formField->getName() . '[\'"]\](.*?)renderFormEnd/s', 'new ' . $className . '$1\$form["' . $formField->getName() . '"]$2renderFormEnd', $phpContent);
        }



        // TODO node visitor

        /** @var string $phpContent */
        $phpContent = preg_replace('#echo \\\\end\(\$this->global->formsStack\)\[[\'"](.*?)[\'"]\](.*?);#', '__latteCompileError(\'Form field with name "$1" probably does not exist.\');', $phpContent);

        return $phpContent;
    }

    /**
     * @return Stmt[]
     */
    private function findNodes(string $phpContent): array
    {
        return $this->parser->parseString($phpContent);
    }
}
