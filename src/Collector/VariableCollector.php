<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedVariable;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TypeResolver;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @phpstan-import-type CollectedVariableArray from CollectedVariable
 * @extends AbstractCollector<Node, CollectedVariable, CollectedVariableArray>
 */
final class VariableCollector extends AbstractCollector
{
    private TypeResolver $typeResolver;

    private TemplateTypeResolver $templateTypeResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        TypeSerializer $typeSerializer,
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        Parser $parser,
        NodeScopeResolver $nodeScopeResolver,
        TypeResolver $typeResolver,
        TemplateTypeResolver $templateTypeResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($typeSerializer, $nameResolver, $reflectionProvider, $parser, $nodeScopeResolver);
        $this->typeResolver = $typeResolver;
        $this->templateTypeResolver = $templateTypeResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @phpstan-return null|CollectedVariableArray[]
     */
    public function collectData(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getTraitReflection() ?: $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $functionName = $scope->getFunctionName();
        if ($functionName === null) {
            return null;
        }

        // TODO add other variable assign resolvers - $template->setParameters(), $template->render(path, parameters) etc.

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
            $variableName = null;
        } else {
            $variableName = is_string($nameNode) ? $nameNode : $nameNode->name;
        }

        $assignVariableType = $scope->getType($var);
        if (!$this->templateTypeResolver->resolve($assignVariableType)) {
            return null;
        }

        $variableType = $this->typeResolver->resolveAsConstantType($node->expr, $scope);
        if ($variableType === null) {
            $variableType = $scope->getType($node->expr);
        }

        if ($variableName !== null) {
            $variables = [CollectedVariable::build($node, $scope, $variableName, $variableType)];
        } else {
            $variables = [];
        }

        $lattePhpDoc = $this->lattePhpDocResolver->resolveForNode($node, $scope);
        if ($lattePhpDoc->isIgnored()) {
            return null;
        }
        if ($lattePhpDoc->getVariables($variableName) !== []) {
            $variables = [];
            foreach ($lattePhpDoc->getVariables($variableName) as $name => $type) {
                $variables[] = CollectedVariable::build($node, $scope, $name, $type);
            }
        }

        return $this->collectItems($variables);
    }
}
