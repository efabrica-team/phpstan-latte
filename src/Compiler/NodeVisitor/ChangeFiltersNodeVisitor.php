<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Closure;
use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FiltersNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FiltersNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ScopeNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ScopeNodeVisitorInterface;
use Efabrica\PHPStanLatte\Template\Variable;
use Latte\Runtime\FilterInfo;
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
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionNamedType;
use PHPStan\BetterReflection\Reflection\ReflectionParameter;
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

final class ChangeFiltersNodeVisitor extends NodeVisitorAbstract implements FiltersNodeVisitorInterface, ExprTypeNodeVisitorInterface, ScopeNodeVisitorInterface
{
    use FiltersNodeVisitorBehavior;
    use ExprTypeNodeVisitorBehavior;
    use ScopeNodeVisitorBehavior;

    private TypeStringResolver $typeStringResolver;

    private ClosureTypeFactory $closureTypeFactory;

    private ReflectionProvider $reflectionProvider;

    public function __construct(
        TypeStringResolver $typeStringResolver,
        ClosureTypeFactory $closureTypeFactory,
        ReflectionProvider $reflectionProvider
    ) {
        $this->typeStringResolver = $typeStringResolver;
        $this->closureTypeFactory = $closureTypeFactory;
        $this->reflectionProvider = $reflectionProvider;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof ClassMethod) {
            $this->addFilterVariables($node);
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

        if (!$dynamicName->var instanceof PropertyFetch) {
            return null;
        }

        if (!$dynamicName->var->var instanceof VariableExpr) {
            return null;
        }

        if (!$dynamicName->var->name instanceof Identifier) {
            return null;
        }

        if ($dynamicName->var->var->name !== 'this' || $dynamicName->var->name->name !== 'filters') {
            return null;
        }

        if (!$dynamicName->name instanceof Identifier) {
            return null;
        }

        $filterName = $dynamicName->name->name;
        $filterName = LatteVersion::isLatte2() ? strtolower($filterName) : $filterName; // latte 3 is case-sensitive with filters
        return $this->createFilterCallNode($filterName, $node->getArgs());
    }

    private function createFilterVariableName(string $filterName): string
    {
        return '__filter__' . (LatteVersion::isLatte2() ? strtolower($filterName) : $filterName);
    }

    /**
     * @param string|array{string, string}|array{object, string}|callable $filter
     */
    private function isCallableString($filter): bool
    {
        return is_string($filter) && (str_starts_with($filter, 'Closure(') || str_starts_with($filter, '\Closure(') || str_starts_with($filter, 'callable('));
    }

    private function addFilterVariables(ClassMethod $node): void
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
        foreach ($this->getFilterVariables() as $variable) {
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
        $variableStatements[] = new Expression(new Assign(new VariableExpr('__filters__'), new ArrayDimFetch(new PropertyFetch(new VariableExpr('this'), 'params'), new String_('filters'))), [
            'comments' => [
                new Doc('/** @var ' . $arrayShape->__toString() . ' $__filters__ */'),
            ],
        ]);

        $variableStatements[] = new Expression(new FuncCall(new Name('extract'), [new Arg(new VariableExpr('__filters__'))]));
        $node->stmts = array_merge($variableStatements, (array)$node->stmts);
    }

    /**
     * @return Variable[]
     */
    private function getFilterVariables(): array
    {
        $variables = [];
        foreach ($this->filters as $filterName => $filter) {
            if ($this->isCallableString($filter)) {
                $variableName = $this->createFilterVariableName($filterName);
                /** @var string $filter */
                $variables[$variableName] = new Variable($variableName, $this->typeStringResolver->resolve($filter));
                continue;
            }

            if ($filter instanceof Closure) {
                $variableName = $this->createFilterVariableName($filterName);
                $variables[$variableName] = new Variable($variableName, $this->closureTypeFactory->fromClosureObject($filter));
                continue;
            }

            if (!is_array($filter)) {
                continue;
            }

            /** @var class-string $className */
            $className = is_string($filter[0]) ? $filter[0] : get_class($filter[0]);
            $methodName = $filter[1];

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

            $variableName = $this->createFilterVariableName($filterName);
            $variables[$variableName] = new Variable($variableName, new ObjectType($className));
        }
        return $variables;
    }

    /**
     * @param Arg[]|VariadicPlaceholder[] $args
     */
    private function createFilterCallNode(string $filterName, array $args): ?Node
    {
        $filter = $this->filters[$filterName] ?? null;
        if ($filter === null) {
            return null;
        }

        if ($filter instanceof Closure || $this->isCallableString($filter)) {
            if ($filter instanceof Closure) {
                $args = $this->updateArgs(ReflectionFunction::createFromClosure($filter), $args);
            }
            return new FuncCall(new VariableExpr($this->createFilterVariableName($filterName)), $args);
        }

        if (is_string($filter)) {
            if (str_contains($filter, '::')) {
                $filter = explode('::', $filter);
            } else {
                $args = $this->updateArgs((new BetterReflection())->reflector()->reflectFunction($filter), $args);
                return new FuncCall(new FullyQualified($filter), $args);
            }
        }

        if (!is_array($filter)) {
            return null;
        }

        /** @var class-string $className */
        $className = is_string($filter[0]) ? $filter[0] : get_class($filter[0]);
        /** @var non-empty-string $methodName */
        $methodName = $filter[1];

        $reflectionClass = (new BetterReflection())->reflector()->reflectClass($className);
        $reflectionMethod = $reflectionClass->getMethod($methodName);

        if ($reflectionMethod === null) {
            return null;
        }

        $args = $this->updateArgs($reflectionMethod, $args);
        if ($reflectionMethod->isStatic()) {
            return new StaticCall(
                new FullyQualified($className),
                new Identifier($methodName),
                $args
            );
        }

        $variableName = $this->createFilterVariableName($filterName);
        return new MethodCall(
            new VariableExpr($variableName),
            new Identifier($methodName),
            $args
        );
    }

    /**
     * @param ReflectionFunction|ReflectionMethod $reflection
     * @param Arg[]|VariadicPlaceholder[] $args
     * @return Arg[]|VariadicPlaceholder[]
     */
    private function updateArgs($reflection, array $args): array
    {
        $parameter = $reflection->getParameters()[0] ?? null;
        if ($parameter instanceof ReflectionParameter && $parameter->getType() instanceof ReflectionNamedType && $parameter->getType()->getName() === FilterInfo::class) {
            $args = array_merge([
                new Arg(new VariableExpr('ʟ_fi')),
            ], $args);
        }
        return $args;
    }
}
