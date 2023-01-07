<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\CustomLatteTemplateResolver\Fixtures;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;
use Efabrica\PHPStanLatte\LatteTemplateResolver\AbstractTemplateResolver;
use Efabrica\PHPStanLatte\LatteTemplateResolver\CustomLatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverResult;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\Variable;
use Nette\Application\UI\Control;
use PHPStan\Type\StringType;

final class TestingCustomTemplateResolver extends AbstractTemplateResolver implements CustomLatteTemplateResolverInterface
{
    private const TEMPLATE_PARAM = 'template';

    public function collect(): array
    {
        return [
            new CollectedResolvedNode(self::class, __FILE__, [self::TEMPLATE_PARAM => __DIR__ . '/templates/default.latte']),
            new CollectedResolvedNode(self::class, __FILE__, [self::TEMPLATE_PARAM => __DIR__ . '/templates/other.latte']),
        ];
    }

    protected function getResult(CollectedResolvedNode $resolvedNode, LatteContextData $latteContext): LatteTemplateResolverResult
    {
        $result = new LatteTemplateResolverResult();
        $result->addTemplate(new Template(
            $resolvedNode->getParam(self::TEMPLATE_PARAM),
            Control::class,
            'resolved',
            [new Variable('someVariable', new StringType())],
            [],
            [],
            []
        ));
        return $result;
    }
}
