<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\AddVarTypesNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\PostCompileNodeVisitorInterface;
use Efabrica\PHPStanLatte\Template\Variable;
use Latte\Compiler;
use Latte\Macros\BlockMacros;
use Latte\Macros\CoreMacros;
use Latte\Parser;
use Nette\Bridges\ApplicationLatte\UIMacros;
use Nette\Bridges\FormsLatte\FormMacros;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

final class LatteToPhpCompiler
{
    private bool $strictMode;

    private Parser $parser;

    private Compiler $compiler;

    /** @var PostCompileNodeVisitorInterface[] */
    private array $postCompileNodeVisitors;

    private Standard $printerStandard;

    /**
     * @param PostCompileNodeVisitorInterface[] $postCompileNodeVisitors
     */
    public function __construct(
        bool $strictMode,
        Parser $parser,
        Compiler $compiler,
        array $postCompileNodeVisitors,
        Standard $printerStandard
    ) {
        $this->strictMode = $strictMode;
        $this->parser = $parser;
        $this->compiler = $compiler;
        $this->postCompileNodeVisitors = $postCompileNodeVisitors;
        $this->printerStandard = $printerStandard;
    }

    /**
     * @param Variable[] $variables
     */
    public function compile(string $templateContent, array $variables): string
    {
        $latteTokens = $this->parser->parse($templateContent);

        $this->installDefaultMacros($this->compiler);
        $phpContent = $this->compiler->compile($latteTokens, 'PHPStanLatteTemplate', null, $this->strictMode);

        return $this->explicitCalls($phpContent, $variables);
    }

    private function installDefaultMacros(Compiler $compiler): void
    {
        // make sure basic macros are installed
        CoreMacros::install($compiler);
        BlockMacros::install($compiler);

        if (class_exists('Nette\Bridges\ApplicationLatte\UIMacros')) {
            UIMacros::install($compiler);
        }

        if (class_exists('Nette\Bridges\FormsLatte\FormMacros')) {
            FormMacros::install($compiler);
        }
    }

    /**
     * @param Variable[] $variables
     */
    private function explicitCalls(string $phpContent, array $variables): string
    {
        $parserFactory = new ParserFactory();

        $phpParser = $parserFactory->create(ParserFactory::PREFER_PHP7);
        $phpStmts = (array)$phpParser->parse($phpContent);

        $nodeTraverser = new NodeTraverser();

        $addVarTypeNodeVisitor = new AddVarTypesNodeVisitor($variables);
        $nodeTraverser->addVisitor($addVarTypeNodeVisitor);

        foreach ($this->postCompileNodeVisitors as $postCompileNodeVisitor) {
            $nodeTraverser->addVisitor($postCompileNodeVisitor);
        }

        $nodeTraverser->traverse($phpStmts);

        return $this->printerStandard->prettyPrintFile($phpStmts);
    }
}
