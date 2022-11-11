<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver\Finder\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;

final class TemplatePathFinderNodeVisitor extends NodeVisitorAbstract
{
    private Scope $scope;

    private TemplateTypeResolver $templateTypeResolver;

    private ValueResolver $valueResolver;

    private ?string $path = null;

    public function __construct(Scope $scope, TemplateTypeResolver $templateTypeResolver, ValueResolver $valueResolver)
    {
        $this->scope = $scope;
        $this->templateTypeResolver = $templateTypeResolver;
        $this->valueResolver = $valueResolver;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof MethodCall) {
            return null;
        }

        if (!$node->name instanceof Identifier) {
            return null;
        }

        if (!in_array($node->name->name, ['setFile', 'render', 'renderToString'], true)) {
            return null;
        }

        $callerType = $this->scope->getType($node->var);
        if (!$this->templateTypeResolver->resolve($callerType)) {
            return null;
        }

        $arg = $node->getArgs()[0] ?? null;
        if (!$arg) {
            return null;
        }

        $this->path = $this->valueResolver->resolve($arg->value, $this->scope);
        return null;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }
}
