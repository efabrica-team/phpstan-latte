<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

use Efabrica\PHPStanLatte\Compiler\Compiler\CompilerInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\AddTypeToComponentNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\AddVarTypesNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\LineNumberNodeVisitor;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\PostCompileNodeVisitorInterface;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;

final class LatteToPhpCompiler
{
    private CompilerInterface $compiler;

    /** @var PostCompileNodeVisitorInterface[] */
    private array $postCompileNodeVisitors;

    private LineNumberNodeVisitor $lineNumberNodeVisitor;

    private Standard $printerStandard;

    /**
     * @param PostCompileNodeVisitorInterface[] $postCompileNodeVisitors
     */
    public function __construct(
        CompilerInterface $compiler,
        array $postCompileNodeVisitors,
        LineNumberNodeVisitor $lineNumberNodeVisitor,
        Standard $printerStandard
    ) {
        $this->compiler = $compiler;
        $this->postCompileNodeVisitors = $postCompileNodeVisitors;
        $this->printerStandard = $printerStandard;
        $this->lineNumberNodeVisitor = $lineNumberNodeVisitor;
    }

    /**
     * @param Variable[] $variables
     * @param Component[] $components
     */
    public function compile(Scope $scope, string $templateContent, array $variables, array $components): string
    {
        $phpContent = $this->compiler->compile($templateContent);
        $phpContent = $this->fixLines($phpContent);
        $phpContent = $this->explicitCalls($scope, $phpContent, $variables, $components);
        return $this->remapLines($phpContent);
    }

    /**
     * @param Variable[] $variables
     * @param Component[] $components
     */
    private function explicitCalls(Scope $scope, string $phpContent, array $variables, array $components): string
    {
        $phpStmts = $this->findNodes($phpContent);

        $nodeTraverser = new NodeTraverser();

        $addVarTypeNodeVisitor = new AddVarTypesNodeVisitor($variables);
        $addVarTypeNodeVisitor->setScope($scope);
        $nodeTraverser->addVisitor($addVarTypeNodeVisitor);

        $addTypeToComponentNodeVisitor = new AddTypeToComponentNodeVisitor($components);
        $nodeTraverser->addVisitor($addTypeToComponentNodeVisitor);

        foreach ($this->postCompileNodeVisitors as $postCompileNodeVisitor) {
            $postCompileNodeVisitor->setScope($scope);
            $nodeTraverser->addVisitor($postCompileNodeVisitor);
        }

        $nodeTraverser->traverse($phpStmts);
        return $this->printerStandard->prettyPrintFile($phpStmts);
    }

    private function fixLines(string $phpContent): string
    {
        $phpContentRows = explode("\n", $phpContent);
        $newPhpContentRows = [];
        foreach ($phpContentRows as $phpContentRow) {
            $pattern = '#/\*(.*?)line (?<number>\d+)(.*?)\*/#';
            preg_match($pattern, $phpContentRow, $matches);

            $latteLine = isset($matches['number']) ? (int)$matches['number'] : null;
            if ($latteLine === null) {
                $newPhpContentRows[] = $phpContentRow;
                continue;
            }
            $newPhpContentRows[] = '/* line ' . $latteLine . ' */';
            $newPhpContentRows[] = preg_replace($pattern, '', $phpContentRow);
        }
        return implode("\n", array_filter($newPhpContentRows));
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
