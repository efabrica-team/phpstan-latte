<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Closure;
use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FiltersNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FiltersNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\TypeToPhpDoc;
use Efabrica\PHPStanLatte\Template\Variable;
use Latte\Runtime\FilterInfo;
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
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionNamedType;
use PHPStan\BetterReflection\Reflection\ReflectionParameter;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Type\ClosureTypeFactory;
use PHPStan\Type\ObjectType;

final class ChangeFiltersNodeVisitor extends NodeVisitorAbstract implements FiltersNodeVisitorInterface
{
    use FiltersNodeVisitorBehavior;

    private TypeStringResolver $typeStringResolver;

    private TypeToPhpDoc $typeToPhpDoc;

    private ClosureTypeFactory $closureTypeFactory;

    public function __construct(TypeStringResolver $typeStringResolver, TypeToPhpDoc $typeToPhpDoc, ClosureTypeFactory $closureTypeFactory)
    {
        $this->typeStringResolver = $typeStringResolver;
        $this->typeToPhpDoc = $typeToPhpDoc;
        $this->closureTypeFactory = $closureTypeFactory;
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
        $variableStatements = [];
        foreach ($this->getFilterVariables() as $variable) {
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

        $variableStatements[] = new Expression(
            new Assign(
                new VariableExpr('__filters__'),
                new VariableExpr('this->filters->getAll()')
            )
        );

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
                $reflectionClass = (new BetterReflection())->reflector()->reflectClass($className);
                $reflectionMethod = $reflectionClass->getMethod($methodName);

                if ($reflectionMethod === null || $reflectionMethod->isStatic()) {
                    continue;
                }

                $variableName = $this->createFilterVariableName($filterName);
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
            if (strpos($filter, '::') === false) {
                $args = $this->updateArgs((new BetterReflection())->reflector()->reflectFunction($filter), $args);
                return new FuncCall(new FullyQualified($filter), $args);
            } else {
                $filter = explode('::', $filter);
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
