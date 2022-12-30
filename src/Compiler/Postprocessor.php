<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

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
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Type\VerbosityLevel;

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

        foreach ($this->nodeVisitorStorage->getNodeVisitors() as $priority => $nodeVisitors) {
            $phpContent = $this->processNodeVisitors($phpContent, $nodeVisitors, $template);
            if ($priority === 300) { // just as back compatibility
                // TODO create visitors for forms
                $phpContent = $this->addFormClasses($phpContent, $template->getForms());
            }
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
        $componentType = 'Nette\ComponentModel\IComponent';
        $addedForms = [];
        foreach ($forms as $form) {
            $formName = $form->getName();
            $className = ucfirst($formName) . '_' . md5(uniqid());

            // TODO node visitor
            /** @var string $phpContent */
            $phpContent = preg_replace('#\$form =(.*?)\$this->global->formsStack\[\] = \$this->global->uiControl\[[\'"]' . $formName . '[\'"]]#', '\$form = new ' . $className . '()', $phpContent);

            // TODO check why there are 5 forms instead of one
            if (isset($addedForms[$formName])) {
                continue;
            }
            $addedForms[$form->getName()] = true;

            $method = (new Method('offsetGet'))
                ->addParam(new Param('name'))
                ->addStmts([
                    new Return_(
                        new StaticCall(
                            new Name('parent'),
                            new Identifier('offsetGet'),
                            [
                                new Arg(new NodeVariable('name')),
                            ]
                        )
                    ),
                ])
                ->makePublic()
                ->setReturnType($componentType);
            $comment = '@return ' . $componentType;
            foreach ($form->getFormFields() as $formField) {
                $comment = str_replace($componentType, '($name is \'' . $formField->getName() . '\' ? ' . $formField->getTypeAsString() . ' : ' . $componentType . ')', $comment);

                // TODO select corresponding part of code and replace all occurences in it, then replace original code with new

                for ($i = 0; $i < 5; $i++) {    // label and input etc.
                    // TODO node visitor
                    /** @var string $phpContent */
                    $phpContent = preg_replace('/new ' . $className . '(.*?)end\(\$this->global->formsStack\)\[[\'"]' . $formField->getName() . '[\'"]\](.*?)renderFormEnd/s', 'new ' . $className . '$1\$form["' . $formField->getName() . '"]$2renderFormEnd', $phpContent);
                }
            }
            $method->setDocComment('/** ' . $comment . ' */');
            $builderClass = (new Class_($className))->extend($form->getType()->describe(VerbosityLevel::typeOnly()))
                ->addStmts([$method]);
            $phpContent .= "\n\n" . $this->printerStandard->prettyPrint([$builderClass->getNode()]);
        }

        // TODO node visitor

        /** @var string $phpContent */
        $phpContent = preg_replace('#echo end\(\$this->global->formsStack\)\[[\'"](.*?)[\'"]\](.*?);#', '__latteCompileError(\'Form field with name "$1" probably does not exist.\');', $phpContent);

        // TODO node visitor
        $phpContent = str_replace('array_pop($this->global->formsStack)', '$form', $phpContent);
        return $phpContent;
    }

    /**
     * @return Stmt[]
     */
    private function findNodes(string $phpContent): array
    {
        $parserFactory = new ParserFactory();
        $phpParser = $parserFactory->create(ParserFactory::PREFER_PHP7);
        return (array)$phpParser->parse($phpContent);
    }
}
