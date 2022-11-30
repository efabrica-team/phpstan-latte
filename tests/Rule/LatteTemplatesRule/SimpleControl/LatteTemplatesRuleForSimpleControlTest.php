<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTest;

final class LatteTemplatesRuleForSimpleControlTest extends LatteTemplatesRuleTest
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
            [
              'Dumped type: string|null',
              1,
              'default.latte',
            ],
            [
                    'Dumped type: string',
                    2,
                    'default.latte',
            ],
            [
              'Dumped type: mixed',
              3,
              'default.latte',
            ],
            [
              'Variable $c might not be defined.',
              3,
              'default.latte',
            ],
        ]);
    }

    public function testThisGetTemplate(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ThisGetTemplate/SomeControl.php'], [
            [
                'Dumped type: string|null',
                1,
                'default.latte',
            ],
            [
                'Dumped type: string',
                2,
                'default.latte',
            ],
            [
                'Dumped type: mixed',
                3,
                'default.latte',
            ],
            [
                'Variable $c might not be defined.',
                3,
                'default.latte',
            ],
        ]);
    }

    public function testTemplateAsVariable(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/TemplateAsVariable/SomeControl.php'], [
            [
                'Dumped type: string|null',
                1,
                'default.latte',
            ],
            [
                'Dumped type: string',
                2,
                'default.latte',
            ],
            [
                'Dumped type: mixed',
                3,
                'default.latte',
            ],
            [
                'Variable $c might not be defined.',
                3,
                'default.latte',
            ],
        ]);
    }

    public function testMultipleRenderMethods(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/MultipleRenderMethods/SomeControl.php'], [
            [
                'Variable $c might not be defined.',
                3,
                'default.latte',
            ],
            [
                'Variable $a might not be defined.',
                1,
                'test.latte',
            ],
            [
                'Variable $b might not be defined.',
                2,
                'test.latte',
            ],
            [
                'Variable $b might not be defined.',
                2,
                'param_a.latte',
            ],
            [
                'Variable $b might not be defined.',
                2,
                'param_b.latte',
            ],
            [
                'Template file "' . __DIR__ . '/Fixtures/MultipleRenderMethods/invalid_file.latte" doesn\'t exist.',
                -1,
                'invalid_file.latte',
            ],
        ]);
    }

    public function testResolveError(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ResolveError/SomeControl.php'], [
            [
                'Cannot resolve latte template for SomeControl::render().',
                11,
                'SomeControl.php',
            ],
            [
                'Cannot resolve latte template for SomeControl::renderNotEvaluated().',
                15,
                'SomeControl.php',
            ],
            [
                'Cannot automatically resolve latte template from expression inside SomeControl::renderNotEvaluatedVar().',
                20,
                'SomeControl.php',
            ],
        ]);
    }
}
