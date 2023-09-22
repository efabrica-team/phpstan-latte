<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\TemplateRenderCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\LatteContext\Collector\AbstractLatteContextSubCollector;
use Efabrica\PHPStanLatte\LatteContext\LatteContextHelper;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\Cast\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\VerbosityLevel;

/**
 * @extends AbstractLatteContextSubCollector<CollectedTemplateRender>
 */
final class CastTemplateToStringCollector extends AbstractLatteContextSubCollector implements TemplateRenderCollectorInterface
{
    private TemplateTypeResolver $templateTypeResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        TemplateTypeResolver $templateTypeResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        $this->templateTypeResolver = $templateTypeResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeTypes(): array
    {
        var_dump('gggg');

        return [String_::class];
    }

    /**
     * @param String_ $node
     */
    public function collect(Node $node, Scope $scope): ?array
    {
        var_dump('xxxxxx');

        $nodeType = $scope->getType($node->expr);

        if (!$this->templateTypeResolver->resolve($nodeType) &&
            !LatteContextHelper::isClass($node->expr, $scope, 'Latte\Engine')
        ) {

var_dump('yyyy');
            return null;
        }

        var_dump('yyy');
        $paths = [null];

        $lattePhpDoc = $this->lattePhpDocResolver->resolveForNode($node->expr, $scope);
        if ($lattePhpDoc->getTemplatePaths() !== []) {
            $paths = $lattePhpDoc->getTemplatePaths();
        }
        return CollectedTemplateRender::buildAll($node, $scope, $paths, []);
    }
}
