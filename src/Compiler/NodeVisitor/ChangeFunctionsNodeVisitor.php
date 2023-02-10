<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Closure;
use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FunctionsNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FunctionsNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\TypeToPhpDoc;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable as VariableExpr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeVisitorAbstract;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Type\ClosureTypeFactory;
use PHPStan\Type\ObjectType;

final class ChangeFunctionsNodeVisitor extends NodeVisitorAbstract implements FunctionsNodeVisitorInterface
{
    use FunctionsNodeVisitorBehavior;

    private TypeStringResolver $typeStringResolver;

    private TypeToPhpDoc $typeToPhpDoc;

    private ClosureTypeFactory $closureTypeFactory;

    private NameResolver $nameResolver;

    public function __construct(
        TypeStringResolver $typeStringResolver,
        TypeToPhpDoc $typeToPhpDoc,
        ClosureTypeFactory $closureTypeFactory,
        NameResolver $nameResolver
    ) {
        $this->typeStringResolver = $typeStringResolver;
        $this->typeToPhpDoc = $typeToPhpDoc;
        $this->closureTypeFactory = $closureTypeFactory;
        $this->nameResolver = $nameResolver;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof ClassMethod) {
            $this->addFunctionVariables($node);
        }

        if (!$node instanceof FuncCall) {
            return null;
        }

        if (!$node->name instanceof Expr) {
            return null;
        }

        $dynamicName = $node->name;
        if (!$dynamicName instanceof PropertyFetch) {
            return null;
        }

        if ($this->nameResolver->resolve($dynamicName->var) !== 'fn') {
            return null;
        }

        if (!$dynamicName->var instanceof PropertyFetch) {
            return null;
        }

        if ($this->nameResolver->resolve($dynamicName->var->var) !== 'global') {
            return null;
        }

        if (!$dynamicName->var->var instanceof PropertyFetch) {
            return null;
        }

        if ($this->nameResolver->resolve($dynamicName->var->var->var) !== 'this') {
            return null;
        }

        $functionName = $this->nameResolver->resolve($node->name);
        if ($functionName === null) {
            return null;
        }

        return $this->createFunctionCallNode($functionName, $node->getArgs());
    }

    private function createFunctionVariableName(string $functionName): string
    {
        return '__function__' . (LatteVersion::isLatte2() ? strtolower($functionName) : $functionName);
    }

    /**
     * @param string|array{string, string}|array{object, string}|callable $function
     */
    private function isCallableString($function): bool
    {
        return is_string($function) && (str_starts_with($function, 'Closure(') || str_starts_with($function, '\Closure(') || str_starts_with($function, 'callable('));
    }

    private function addFunctionVariables(ClassMethod $node): void
    {
        $variableStatements = [];
        foreach ($this->getFunctionVariables() as $variable) {
            $prependVarTypesDocBlocks = sprintf(
                '/** @var %s $%s */',
                $this->typeToPhpDoc->toPhpDocString($variable->getType()),
                $variable->getName()
            );

            // doc types node
            $docNop = new Nop();
            $docNop->setDocComment(new Doc($prependVarTypesDocBlocks));
            $variableStatements[] = $docNop;
        }

        if ($variableStatements !== []) {
            $variableStatements[] = new Expression(
                new Assign(
                    new VariableExpr('__functions__'),
                    new VariableExpr('this->global->fn')
                )
            );
        }

        $node->stmts = array_merge($variableStatements, (array)$node->stmts);
    }

    /**
     * @return Variable[]
     */
    private function getFunctionVariables(): array
    {
        $variables = [];
        foreach ($this->functions as $functionName => $function) {
            if ($this->isCallableString($function)) {
                $variableName = $this->createFunctionVariableName($functionName);
                /** @var string $function */
                $variables[$variableName] = new Variable($variableName, $this->typeStringResolver->resolve($function));
                continue;
            }

            if ($function instanceof Closure) {
                $variableName = $this->createFunctionVariableName($functionName);
                $variables[$variableName] = new Variable($variableName, $this->closureTypeFactory->fromClosureObject($function));
                continue;
            }

            if (!is_array($function)) {
                continue;
            }

            /** @var class-string $className */
            $className = is_string($function[0]) ? $function[0] : get_class($function[0]);
            $methodName = $function[1];

            if ($methodName === '') {
                continue;
            }

            try {
                $reflectionClass = (new BetterReflection())->reflector()->reflectClass($className);
                $reflectionMethod = $reflectionClass->getMethod($methodName);

                if ($reflectionMethod === null || $reflectionMethod->isStatic()) {
                    continue;
                }

                $variableName = $this->createFunctionVariableName($functionName);
                $variables[$variableName] = new Variable($variableName, new ObjectType($className));
            } catch (ClassNotFoundException $e) {
                continue;
            }
        }
        return $variables;
    }

    /**
     * @param Arg[]|VariadicPlaceholder[] $args
     */
    private function createFunctionCallNode(string $functionName, array $args): ?Node
    {
        $function = $this->functions[$functionName] ?? null;
        if ($function === null) {
            return null;
        }

        if ($function instanceof Closure || $this->isCallableString($function)) {
            return new FuncCall(new VariableExpr($this->createFunctionVariableName($functionName)), $args);
        }

        if (is_string($function)) {
            return new FuncCall(new FullyQualified($function), $args);
        }

        if (!is_array($function)) {
            return null;
        }

        /** @var class-string $className */
        $className = is_string($function[0]) ? $function[0] : get_class($function[0]);
        /** @var non-empty-string $methodName */
        $methodName = $function[1];

        $reflectionClass = (new BetterReflection())->reflector()->reflectClass($className);
        $reflectionMethod = $reflectionClass->getMethod($methodName);

        if ($reflectionMethod === null) {
            return null;
        }

        if ($reflectionMethod->isStatic()) {
            return new StaticCall(
                new FullyQualified($className),
                new Identifier($methodName),
                $args
            );
        }

        $variableName = $this->createFunctionVariableName($functionName);
        return new MethodCall(
            new VariableExpr($variableName),
            new Identifier($methodName),
            $args
        );
    }
}
