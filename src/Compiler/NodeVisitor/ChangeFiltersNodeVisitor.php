<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ScopedNodeVisitorBehavior;
use Latte\Runtime\Defaults;
use Nette\Utils\Strings;
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
use ReflectionClass;
use ReflectionException;

final class ChangeFiltersNodeVisitor extends NodeVisitorAbstract implements PostCompileNodeVisitorInterface
{
    use ScopedNodeVisitorBehavior;

    /** @var array<string, string|array{string, string}> */
    private array $filters;

    /**
     * @param array<string, string|array{string, string}> $filters
     */
    public function __construct(array $filters)
    {
        $defaults = new Defaults();
        /** @var array<string, string|array{string, string}> $defaultFilters */
        $defaultFilters = array_change_key_case($defaults->getFilters());
        $this->filters = $defaultFilters;

        foreach ($filters as $filterName => $filter) {
            $this->filters[strtolower($filterName)] = $filter;
        }
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

        $filterName = strtolower($dynamicName->name->name);
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

        if (is_string($filter)) {
            return new FuncCall(new FullyQualified($filter), $args);
        }

        /** @var class-string $className */
        $className = $filter[0];
        $methodName = $filter[1];

        try {
            $reflectionClass = new ReflectionClass($className);
            $reflectionMethod = $reflectionClass->getMethod($methodName);
        } catch (ReflectionException $exception) {
            return null;
        }

        if ($reflectionMethod->isStatic()) {
            return new StaticCall(
                new FullyQualified($className),
                new Identifier($methodName),
                $args
            );
        }

        // TODO create helper
        $variableName = Strings::firstLower(Strings::replace($className, '#\\\#', '')) . 'Filter';
        return new MethodCall(
            new Variable($variableName),
            new Identifier($methodName),
            $args
        );
    }
}
