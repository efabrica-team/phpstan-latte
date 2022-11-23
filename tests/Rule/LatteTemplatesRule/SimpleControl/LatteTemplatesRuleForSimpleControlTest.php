<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTest;
use Latte\Engine;

final class LatteTemplatesRuleForSimpleControlTest extends LatteTemplatesRuleTest
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../extension.neon',
            Engine::VERSION_ID < 30000 ? __DIR__ . '/../../../../latte2.neon' : __DIR__ . '/../../../../latte3.neon',
            __DIR__ . '/../../../../rules.neon',
        ];
    }

    public function test(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ThisTemplate/SomeControl.php'], [
            [
                'Variable $c might not be defined.',
                3,
                'default.latte',
            ],
        ]);

        $this->analyse([__DIR__ . '/Fixtures/ThisGetTemplate/SomeControl.php'], [
            [
                'Variable $c might not be defined.',
                3,
                'default.latte',
            ],
        ]);

//        $template = $this->template not working
//        $this->analyse([__DIR__ . '/Fixtures/TemplateAsVariable/SomeControl.php'], [
//            [
//                'Variable $c might not be defined.',
//                3,
//            ],
//        ]);
    }
}
