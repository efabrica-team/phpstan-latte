<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedForm;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\AddExtractParamsToTopNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\AddTypeToComponentNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\AddVarTypesNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\CleanupNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\LineNumberNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\PostCompileNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\RenderBlockNodeVisitor;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Variable;
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
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Type\VerbosityLevel;

final class Postprocessor
{
    /** @var PostCompileNodeVisitorInterface[] */
    private array $postCompileNodeVisitors;

    private LineNumberNodeVisitor $lineNumberNodeVisitor;

    private RenderBlockNodeVisitor $renderBlockNodeVisitor;

    private CleanupNodeVisitor $cleanupNodeVisitor;

    private Standard $printerStandard;

    private TypeToPhpDoc $typeToPhpDoc;

    /**
     * @param PostCompileNodeVisitorInterface[] $postCompileNodeVisitors
     */
    public function __construct(
        array $postCompileNodeVisitors,
        LineNumberNodeVisitor $lineNumberNodeVisitor,
        RenderBlockNodeVisitor $renderBlockNodeVisitor,
        CleanupNodeVisitor $cleanupNodeVisitor,
        Standard $printerStandard,
        TypeToPhpDoc $typeToPhpDoc
    ) {
        $this->postCompileNodeVisitors = $postCompileNodeVisitors;
        $this->lineNumberNodeVisitor = $lineNumberNodeVisitor;
        $this->renderBlockNodeVisitor = $renderBlockNodeVisitor;
        $this->cleanupNodeVisitor = $cleanupNodeVisitor;
        $this->printerStandard = $printerStandard;
        $this->typeToPhpDoc = $typeToPhpDoc;
    }

    /**
     * @param Variable[] $variables
     * @param Component[] $components
     * @param CollectedForm[] $forms
     */
    public function postProcess(?string $actualClass, string $phpContent, array $variables, array $components, array $forms): string
    {
        $phpContent = $this->explicitCalls($actualClass, $phpContent, $variables, $components);
        $phpContent = $this->addExtractParams($phpContent);
        $phpContent = $this->addFormClasses($phpContent, $forms);
        $phpContent = $this->explicitRenderBlockCalls($phpContent);
        $phpContent = $this->cleanup($phpContent);
        return $this->remapLines($phpContent);
    }

    /**
     * @param Variable[] $variables
     * @param Component[] $components
     */
    private function explicitCalls(?string $actualClass, string $phpContent, array $variables, array $components): string
    {
        $phpStmts = $this->findNodes($phpContent);

        $nodeTraverser = new NodeTraverser();

        $addVarTypeNodeVisitor = new AddVarTypesNodeVisitor($variables, $this->typeToPhpDoc);
        $addVarTypeNodeVisitor->setActualClass($actualClass);
        $nodeTraverser->addVisitor($addVarTypeNodeVisitor);

        $addTypeToComponentNodeVisitor = new AddTypeToComponentNodeVisitor($components, $this->typeToPhpDoc);
        $nodeTraverser->addVisitor($addTypeToComponentNodeVisitor);

        foreach ($this->postCompileNodeVisitors as $postCompileNodeVisitor) {
            $postCompileNodeVisitor->setActualClass($actualClass);
            $nodeTraverser->addVisitor($postCompileNodeVisitor);
        }

        $nodeTraverser->traverse($phpStmts);
        return $this->printerStandard->prettyPrintFile($phpStmts);
    }

    private function addExtractParams(string $phpContent): string
    {
        $phpStmts = $this->findNodes($phpContent);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new AddExtractParamsToTopNodeVisitor());
        $nodeTraverser->traverse($phpStmts);

        return $this->printerStandard->prettyPrintFile($phpStmts);
    }

    private function explicitRenderBlockCalls(string $phpContent): string
    {
        $phpStmts = $this->findNodes($phpContent);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($this->renderBlockNodeVisitor);
        $nodeTraverser->traverse($phpStmts);

        return $this->printerStandard->prettyPrintFile($phpStmts);
    }

    private function cleanup(string $phpContent): string
    {
        $phpStmts = $this->findNodes($phpContent);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($this->cleanupNodeVisitor);
        $nodeTraverser->traverse($phpStmts);

        return $this->printerStandard->prettyPrintFile($phpStmts);
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

    private function remapLines(string $phpContent): string
    {
        $phpStmts = $this->findNodes($phpContent);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($this->lineNumberNodeVisitor);
        $nodeTraverser->traverse($phpStmts);

        return $this->printerStandard->prettyPrintFile($phpStmts);
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
