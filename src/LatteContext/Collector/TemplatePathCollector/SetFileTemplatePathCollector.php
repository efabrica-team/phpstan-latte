<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\TemplatePathCollector;

use Efabrica\PHPStanLatte\LatteContext\Collector\AbstractLatteContextSubCollector;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;

/**
 * @extends AbstractLatteContextSubCollector<string>
 */
final class SetFileTemplatePathCollector extends AbstractLatteContextSubCollector implements TemplatePathCollectorInterface
{
    private NameResolver $nameResolver;

    private PathResolver $pathResolver;

    private TemplateTypeResolver $templateTypeResolver;

    public function __construct(
        NameResolver $nameResolver,
        PathResolver $pathResolver,
        TemplateTypeResolver $templateTypeResolver
    ) {
        $this->nameResolver = $nameResolver;
        $this->pathResolver = $pathResolver;
        $this->templateTypeResolver = $templateTypeResolver;
    }

    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function collect(Node $node, Scope $scope): ?array
    {
        $calledMethodName = $this->nameResolver->resolve($node);
        if (!in_array($calledMethodName, ['setFile'], true)) {
            return [];
        }

        if (!$this->templateTypeResolver->resolveByNodeAndScope($node, $scope)) {
            return [];
        }

        $arg = $node->getArgs()[0] ?? null;
        if (!$arg) {
            return [];
        }

        return $this->pathResolver->resolve($arg->value, $scope);
    }
}
