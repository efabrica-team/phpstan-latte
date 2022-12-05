<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedForm;
use Efabrica\PHPStanLatte\Compiler\Compiler\CompilerInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\AddExtractParamsToTopNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\AddTypeToComponentNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\AddVarTypesNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\LineNumberNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\PostCompileNodeVisitorInterface;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Variable;
use InvalidArgumentException;
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

final class LatteToPhpCompiler
{
    private string $tmpDir;

    private CompilerInterface $compiler;

    /** @var PostCompileNodeVisitorInterface[] */
    private array $postCompileNodeVisitors;

    private LineNumberNodeVisitor $lineNumberNodeVisitor;

    private Standard $printerStandard;

    /**
     * @param PostCompileNodeVisitorInterface[] $postCompileNodeVisitors
     */
    public function __construct(
        ?string $tmpDir,
        CompilerInterface $compiler,
        array $postCompileNodeVisitors,
        LineNumberNodeVisitor $lineNumberNodeVisitor,
        Standard $printerStandard
    ) {
        $this->tmpDir = $tmpDir ?? sys_get_temp_dir() . '/phpstan-latte';
        $this->compiler = $compiler;
        $this->postCompileNodeVisitors = $postCompileNodeVisitors;
        $this->printerStandard = $printerStandard;
        $this->lineNumberNodeVisitor = $lineNumberNodeVisitor;
    }

    /**
     * @param Variable[] $variables
     * @param Component[] $components
     * @param CollectedForm[] $forms
     */
    public function compileFile(?string $actualClass, string $templatePath, array $variables, array $components, array $forms, string $context = ''): string
    {
        if (!file_exists($templatePath)) {
            throw new InvalidArgumentException('Template file "' . $templatePath . '" doesn\'t exist.');
        }
        $templateContent = file_get_contents($templatePath) ?: '';
        $phpContent = $this->compile($actualClass, $templateContent, $variables, $components, $forms, $context);
        $templateDir = pathinfo($templatePath, PATHINFO_DIRNAME);
        $templateFileName = pathinfo($templatePath, PATHINFO_BASENAME);
        $contextHash = md5(
            $actualClass .
            json_encode($variables) .
            json_encode($components) .
            $context
        );

        $replacedPath = getcwd() ?: '';
        if (strpos($templateDir, $replacedPath) === 0) {
            $templateDir = substr($templateDir, strlen($replacedPath));
        }

        $compileDir = $this->tmpDir . '/' . $templateDir;
        if (!file_exists($compileDir)) {
            mkdir($compileDir, 0777, true);
        }
        $compileFilePath = $compileDir . '/' . $templateFileName . '.' . $contextHash . '.php';
        file_put_contents($compileFilePath, $phpContent);
        return $compileFilePath;
    }

    /**
     * @param Variable[] $variables
     * @param Component[] $components
     * @param CollectedForm[] $forms
     */
    private function compile(?string $actualClass, string $templateContent, array $variables, array $components, array $forms, string $context = ''): string
    {
        $phpContent = $this->compiler->compile($templateContent, $actualClass, $context);
        $phpContent = $this->explicitCalls($actualClass, $phpContent, $variables, $components);
        $phpContent = $this->addExtractParams($phpContent);
        $phpContent = $this->addFormClasses($phpContent, $forms);
        return $this->remapLines($phpContent);
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
            $phpContent = preg_replace('#\$form = \$this->global->formsStack\[\] = \$this->global->uiControl\[[\'"]' . $formName . '[\'"]]#', '\$form = new ' . $className . '()', $phpContent);

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
                $comment = str_replace($componentType, '($name is \'' . $formField->getName() . '\' ? ' . $formField->getType() . ' : ' . $componentType . ')', $comment);

                // TODO select corresponding part of code and replace all occurences in it, then replace original code with new

                for ($i = 0; $i < 5; $i++) {    // label and input etc.
                    // TODO node visitor
                    /** @var string $phpContent */
                    $phpContent = preg_replace('/new ' . $className . '(.*?)end\(\$this->global->formsStack\)\[[\'"]' . $formField->getName() . '[\'"]\](.*?)renderFormEnd/s', 'new ' . $className . '$1\$form["' . $formField->getName() . '"]$2renderFormEnd', $phpContent);
                }
            }
            $method->setDocComment('/** ' . $comment . ' */');
            $builderClass = (new Class_($className))->extend('Nette\Forms\Form')
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
     * @param Variable[] $variables
     * @param Component[] $components
     */
    private function explicitCalls(?string $actualClass, string $phpContent, array $variables, array $components): string
    {
        $phpStmts = $this->findNodes($phpContent);

        $nodeTraverser = new NodeTraverser();

        $addVarTypeNodeVisitor = new AddVarTypesNodeVisitor($variables);
        $addVarTypeNodeVisitor->setActualClass($actualClass);
        $nodeTraverser->addVisitor($addVarTypeNodeVisitor);

        $addTypeToComponentNodeVisitor = new AddTypeToComponentNodeVisitor($components);
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
