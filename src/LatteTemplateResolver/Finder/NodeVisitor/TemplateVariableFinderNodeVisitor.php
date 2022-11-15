<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver\Finder\NodeVisitor;

use Efabrica\PHPStanLatte\LatteTemplateResolver\Finder\TemplateVariableFinder;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Template\Variable as TemplateVariable;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PHPStan\Analyser\Scope;
use ReflectionException;
use ReflectionMethod;

final class TemplateVariableFinderNodeVisitor extends NodeVisitorAbstract
{
    private Scope $scope;

    private TemplateTypeResolver $templateTypeResolver;

    private TemplateVariableFinder $templateVariableFinder;

    /** @var TemplateVariable[] */
    private array $variables = [];

    public function __construct(
        Scope $scope,
        TemplateTypeResolver $templateTypeResolver,
        TemplateVariableFinder $templateVariableFinder
    ) {
        $this->scope = $scope;
        $this->templateTypeResolver = $templateTypeResolver;
        $this->templateVariableFinder = $templateVariableFinder;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof MethodCall || $node instanceof StaticCall) {
            $this->variables = array_merge($this->variables, $this->processMethodCall($node));
            return null;
        }

        if (!$node instanceof Assign) {
            return null;
        }

        if ($node->var instanceof Variable) {
            $var = $node->var;
            $nameNode = $node->var->name;
        } elseif ($node->var instanceof PropertyFetch) {
            $var = $node->var->var;
            $nameNode = $node->var->name;
        } else {
            return null;
        }

        if ($nameNode instanceof Expr) {
            return null;
        }

        $variableType = $this->scope->getType($var);
        if (!$this->templateTypeResolver->resolve($variableType)) {
            return null;
        }

        $variableName = is_string($nameNode) ? $nameNode : $nameNode->name;
        $this->variables[] = new TemplateVariable($variableName, $this->scope->getType($node->expr));
        return null;
    }

    /**
     * @param MethodCall|StaticCall $methodCall
     * @return TemplateVariable[]
     */
    private function processMethodCall(CallLike $methodCall): array
    {
        $classReflection = $this->scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        if (!$methodCall->name instanceof Identifier) {
            return [];
        }

        $calledMethodName = $methodCall->name->name;

        $caller = $classReflection->getName();
        if ($methodCall instanceof StaticCall && $methodCall->class instanceof Name && (string)$methodCall->class === 'parent' && $classReflection->getParentClass() !== null) {
            $caller = $classReflection->getParentClass()->getName();
        }

        try {
            $reflectionMethod = new ReflectionMethod($caller, $calledMethodName);
        } catch (ReflectionException $e) {
            return [];
        }

        $methodFileName = $reflectionMethod->getFileName();
        if ($methodFileName === false) {
            return [];
        }

        $phpContent = file_get_contents($methodFileName) ?: '';
        if ($phpContent === '') {
            return [];
        }
        $parserFactory = new ParserFactory();
        $phpParser = $parserFactory->create(ParserFactory::PREFER_PHP7);
        $nodes = (array)$phpParser->parse($phpContent);

        $nodeFinder = new NodeFinder();
        /** @var ClassMethod[] $classMethods */
        $classMethods = $nodeFinder->find($nodes, function (Node $node) use ($calledMethodName) {
            return $node instanceof ClassMethod && $node->name->name === $calledMethodName;
        });

        $variables = [];
        foreach ($classMethods as $classMethod) {
            $variables = array_merge($variables, $this->templateVariableFinder->find($classMethod, $this->scope));
        }
        return $variables;
    }

    /**
     * @return TemplateVariable[]
     */
    public function getVariables(): array
    {
        return $this->variables;
    }
}
