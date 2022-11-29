<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\CollectorResultTest;

final class CollectorResultForSimpleControlTest extends CollectorResultTest
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/../../../config.neon',
        ];
    }

    public function testThisTemplate(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ThisTemplate/SomeControl.php'], [
            'NODE NetteApplicationUIControl {"className":"\SimpleControl\Fixtures\ThisTemplate\SomeControl"}',
            'TEMPLATE default.latte SomeControl ["actualClass","presenter","a","a","b"] []',

        ]);
    }

    public function testThisGetTemplate(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ThisGetTemplate/SomeControl.php'], [
            'NODE NetteApplicationUIControl {"className":"\SimpleControl\Fixtures\ThisGetTemplate\SomeControl"}',
            'TEMPLATE default.latte SomeControl ["actualClass","presenter","a","a","b"] []',
        ]);
    }

    public function testTemplateAsVariable(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/TemplateAsVariable/SomeControl.php'], [
            'NODE NetteApplicationUIControl {"className":"\SimpleControl\Fixtures\TemplateAsVariable\SomeControl"}',
            'TEMPLATE default.latte SomeControl ["actualClass","presenter","a","a","b"] []',
        ]);
    }

    public function testMultipleRenderMethods(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/MultipleRenderMethods/SomeControl.php'], [
            'NODE NetteApplicationUIControl {"className":"\SimpleControl\Fixtures\MultipleRenderMethods\SomeControl"}',
            'TEMPLATE default.latte SomeControl ["actualClass","presenter","a","b"] []',
            'TEMPLATE test.latte SomeControl ["actualClass","presenter","c","d"] []',
            'TEMPLATE invalid_file.latte SomeControl ["actualClass","presenter"] []',
        ]);
    }
}
