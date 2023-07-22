<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Closure;
use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FunctionsNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FunctionsNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ScopeNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ScopeNodeVisitorInterface;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable as VariableExpr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ClosureTypeFactory;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ThisType;

final class ChangeFunctionsNodeVisitor extends NodeVisitorAbstract implements FunctionsNodeVisitorInterface, ExprTypeNodeVisitorInterface, ScopeNodeVisitorInterface
{
    use FunctionsNodeVisitorBehavior;
    use ExprTypeNodeVisitorBehavior;
    use ScopeNodeVisitorBehavior;

    private TypeStringResolver $typeStringResolver;

    private ClosureTypeFactory $closureTypeFactory;

    private NameResolver $nameResolver;

    private ReflectionProvider $reflectionProvider;

    public function __construct(
        TypeStringResolver $typeStringResolver,
        ClosureTypeFactory $closureTypeFactory,
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider
    ) {
        $this->typeStringResolver = $typeStringResolver;
        $this->closureTypeFactory = $closureTypeFactory;
        $this->nameResolver = $nameResolver;
        $this->reflectionProvider = $reflectionProvider;
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
        $class = $node->getAttribute('parent');
        if (!$class instanceof Class_) {
            return;
        }

        $type = $this->getType($class);
        if ($type === null || !(new ObjectType('\Latte\Runtime\Template'))->isSuperTypeOf($type)->yes()) {
            return;
        }

        $arrayShapeItems = [];
        foreach ($this->getFunctionVariables() as $variable) {
            $variableType = $variable->getType();

            if ($variableType instanceof ThisType) {
                // $this(SomeClass) is transformed to $this, but we want to use SomeClass instead
                $variableType = $variableType->getStaticObjectType();
            }

            $arrayShapeItems[] = new ArrayShapeItemNode(new ConstExprStringNode($variable->getName()), $variable->mightBeUndefined(), $variableType->toPhpDocNode());
        }

        if ($arrayShapeItems === []) {
            return;
        }

        $arrayShape = new ArrayShapeNode($arrayShapeItems);

        $variableStatements = [];
        $variableStatements[] = new Expression(new Assign(new VariableExpr('__functions__'), new ArrayDimFetch(new PropertyFetch(new VariableExpr('this'), 'params'), new String_('functions'))), [
            'comments' => [
                new Doc('/** @var ' . $arrayShape->__toString() . ' $__functions__ */'),
            ],
        ]);

        $variableStatements[] = new Expression(new FuncCall(new Name('extract'), [new Arg(new VariableExpr('__functions__'))]));
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
                $classReflection = $this->reflectionProvider->getClass($className);
            } catch (ClassNotFoundException $e) {
                continue;
            }

            try {
                $methodReflection = $classReflection->getMethod($methodName, $this->getScope());
            } catch (MissingMethodFromReflectionException $e) {
                continue;
            }

            if ($methodReflection->isStatic()) {
                continue;
            }

            $variableName = $this->createFunctionVariableName($functionName);
            $variables[$variableName] = new Variable($variableName, new ObjectType($className));
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
            if (str_contains($function, '::')) {
                $function = explode('::', $function);
            } else {
                return new FuncCall(new FullyQualified($function), $args);
            }
        }

        if (!is_array($function)) {
            return null;
        }

        /** @var class-string $className */
        $className = is_string($function[0]) ? $function[0] : get_class($function[0]);
        /** @var non-empty-string $methodName */
        $methodName = $function[1];

        try {
            $classReflection = $this->reflectionProvider->getClass($className);
        } catch (ClassNotFoundException $e) {
            return null;
        }

        try {
            $methodReflection = $classReflection->getMethod($methodName, $this->getScope());
        } catch (MissingMethodFromReflectionException $e) {
            return null;
        }

        if ($methodReflection->isStatic()) {
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
