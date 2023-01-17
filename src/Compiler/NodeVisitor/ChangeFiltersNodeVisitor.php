<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Closure;
use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\Helper\FilterHelper;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeVisitorAbstract;
use PHPStan\BetterReflection\BetterReflection;

final class ChangeFiltersNodeVisitor extends NodeVisitorAbstract
{
    /** @var array<string, string|array{string, string}|array{object, string}|callable> */
    private array $filters;

    /**
     * @param array<string, string|array{string, string}|array{object, string}|callable> $filters
     */
    public function __construct(array $filters)
    {
        $this->filters = LatteVersion::isLatte2() ? array_change_key_case($filters) : $filters;
    }

    public function enterNode(Node $node): ?Node
    {
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

        if (!$dynamicName->var->var instanceof Variable) {
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

    /**
     * @param Arg[]|VariadicPlaceholder[] $args
     */
    private function createFilterCallNode(string $filterName, array $args): ?Node
    {
        $filter = $this->filters[$filterName] ?? null;
        if ($filter === null) {
            return null;
        }

        // Add FilterInfo for special filters
        if (in_array($filterName, ['striphtml', 'striptags', 'strip', 'indent', 'repeat', 'replace', 'trim'], true)) {
            $args = array_merge([
                new Arg(new Variable('ʟ_fi')),
            ], $args);
        }

        if ($filter instanceof Closure || FilterHelper::isCallableString($filter)) {
            return new FuncCall(new Variable(FilterHelper::createFilterVariableName($filterName)), $args);
        }

        if (is_string($filter)) {
            return new FuncCall(new FullyQualified($filter), $args);
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

        if ($reflectionMethod->isStatic()) {
            return new StaticCall(
                new FullyQualified($className),
                new Identifier($methodName),
                $args
            );
        }

        $variableName = FilterHelper::createFilterVariableName($filterName);
        return new MethodCall(
            new Variable($variableName),
            new Identifier($methodName),
            $args
        );
    }
}
