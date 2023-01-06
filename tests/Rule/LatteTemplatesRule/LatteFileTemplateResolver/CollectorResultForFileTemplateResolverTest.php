<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\EngineBootstrap;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\CollectorResultTest;

final class CollectorResultForFileTemplateResolverTest extends CollectorResultTest
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/../../../config.neon',
            __DIR__ . '/config.neon',
        ];
    }

    public function testResolver(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/SomeControl.php'], [
            'NODE NetteApplicationUIControl {"className":"SomeControl"}',
            'NODE TestingFileTemplateResolver {"template":"/LatteFileTemplateResolver/Fixtures/templates/default.latte"}',
            'TEMPLATE default.latte Control::resolved ["someVariable"] []',
        ]);
    }
}
