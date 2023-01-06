<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteFileTemplateResolver\Fixtures;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;
use Efabrica\PHPStanLatte\LatteTemplateResolver\AbstractTemplateResolver;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteFileTemplateResolverInterface;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverResult;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\Variable;
use Nette\Application\UI\Control;
use PHPStan\Type\StringType;

final class TestingFileTemplateResolver extends AbstractTemplateResolver implements LatteFileTemplateResolverInterface
{
    private const TEMPLATE_PARAM = 'template';

    public function collect(string $templateFile): ?CollectedResolvedNode
    {
        return new CollectedResolvedNode(self::class, __FILE__, [self::TEMPLATE_PARAM => $templateFile]);
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
