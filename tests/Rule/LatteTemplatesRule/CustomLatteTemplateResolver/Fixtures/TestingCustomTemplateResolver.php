<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\CustomLatteTemplateResolver\Fixtures;

use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;
use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\LatteTemplateResolver\CustomLatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverResult;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\TemplateContext;
use Efabrica\PHPStanLatte\Template\Variable;
use Nette\Application\UI\Control;
use PHPStan\Type\StringType;

final class TestingCustomTemplateResolver implements CustomLatteTemplateResolverInterface
{
    private const TEMPLATE_PARAM = 'template';

    public function collect(): array
    {
        return [
            new CollectedResolvedNode(self::class, __FILE__, [self::TEMPLATE_PARAM => __DIR__ . '/templates/default.latte']),
            new CollectedResolvedNode(self::class, __FILE__, [self::TEMPLATE_PARAM => __DIR__ . '/templates/other.latte']),
        ];
    }

    public function resolve(CollectedResolvedNode $resolvedNode, LatteContext $latteContext): LatteTemplateResolverResult
    {
        $result = new LatteTemplateResolverResult();
        $result->addTemplate(new Template(
            $resolvedNode->getParam(self::TEMPLATE_PARAM),
            Control::class,
            'resolved',
            new TemplateContext(
                [new Variable('someVariable', new StringType())],
            ),
        ));
        return $result;
    }
}
