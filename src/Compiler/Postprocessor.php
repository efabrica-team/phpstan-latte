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
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ScopeNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\VariablesNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\NodeVisitorStorage;
use Efabrica\PHPStanLatte\Exception\ParseException;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Template\Template;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\Type\ObjectType;

final class Postprocessor
{
    private Parser $parser;

    private NodeVisitorStorage $nodeVisitorStorage;

    private Standard $printerStandard;

    private CompilerInterface $compiler;

    private ScopeFactory $scopeFactory;

    private NodeScopeResolver $nodeScopeResolver;

    private NameResolver $nameResolver;

    public function __construct(
        Parser $parser,
        NodeVisitorStorage $nodeVisitorStorage,
        Standard $printerStandard,
        CompilerInterface $compiler,
        ScopeFactory $scopeFactory,
        NodeScopeResolver $nodeScopeResolver,
        NameResolver $nameResolver
    ) {
        $this->parser = $parser;
        $this->nodeVisitorStorage = $nodeVisitorStorage;
        $this->printerStandard = $printerStandard;
        $this->compiler = $compiler;
        $this->scopeFactory = $scopeFactory;
        $this->nodeScopeResolver = $nodeScopeResolver;
        $this->nameResolver = $nameResolver;
    }

    public function getCacheKey(): string
    {
        $signature = '';
        foreach ($this->nodeVisitorStorage->getNodeVisitors() as $nodeVisitors) {
            foreach ($nodeVisitors as $nodeVisitor) {
                $signature .= $nodeVisitor::class;
            }
        }
        return md5($signature);
    }

    /**
     * @throws ParseException
     */
    public function postProcess(string $phpContent, Template $template, string $compileFilePath): string
    {
        $phpStmts = $this->findNodes($phpContent, $compileFilePath);
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

        $phpStmts = $this->findNodes($phpContent, $compileFilePath);

        $nodeScopeResolver = clone $this->nodeScopeResolver;
        $nodeScopeResolver->processNodes($phpStmts, $scope, function (Node $node, Scope $scope) {
            if ($node instanceof Expr) {
                $node->setAttribute(ExprTypeNodeVisitorInterface::TYPE_ATTRIBUTE, $scope->getType($node));
            } elseif ($node instanceof Class_ || $node instanceof Interface_) {
                /** @var string $name */
                $name = $this->nameResolver->resolve($node->name);
                $node->setAttribute(ExprTypeNodeVisitorInterface::TYPE_ATTRIBUTE, new ObjectType($name));
            }
        });

        foreach ($this->nodeVisitorStorage->getNodeVisitors(true) as $nodeVisitors) {
            $phpStmts = $this->processNodeVisitors($phpStmts, $nodeVisitors, $template, $scope);
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
    private function processNodeVisitors(array $phpStmts, array $nodeVisitors, Template $template, ?Scope $scope = null): array
    {
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ParentConnectingVisitor()); // symplify/phpstan-rules compatibility
        foreach ($nodeVisitors as $cleanNodeVisitor) {
            $nodeVisitor = clone $cleanNodeVisitor;
            $this->setupVisitor($nodeVisitor, $template, $scope);
            $nodeTraverser->addVisitor($nodeVisitor);
        }

        return $nodeTraverser->traverse($phpStmts);
    }

    private function setupVisitor(NodeVisitor $nodeVisitor, Template $template, ?Scope $scope = null): void
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
        if ($nodeVisitor instanceof ScopeNodeVisitorInterface && $scope !== null) {
            $nodeVisitor->setScope($scope);
        }
    }

    /**
     * @return Node[]
     * @throws ParseException
     */
    private function findNodes(string $phpContent, string $compileFilePath): array
    {
        try {
            return $this->parser->parseString($phpContent);
        } catch (ParserErrorsException $e) {
            // save original php content to better debugging
            file_put_contents($compileFilePath, $phpContent);

            preg_match('/ on line (?<line>\d+)/', $e->getMessage(), $matches);
            $message = preg_replace('/ on line (?<line>\d+)/', '', $e->getMessage());
            $line = (int)($matches['line'] ?? 0);
            throw new ParseException($message ?: 'Parse error', $line, $compileFilePath);
        }
    }
}
