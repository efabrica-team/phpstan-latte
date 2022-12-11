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
                'Dumped type: \'a\'|null',
                1,
                'default.latte',
            ],
            [
                'Dumped type: \'b\'',
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
                'Dumped type: \'a\'|null',
                1,
                'default.latte',
            ],
            [
                'Dumped type: \'b\'',
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
                'Dumped type: \'a\'|null',
                1,
                'default.latte',
            ],
            [
                'Dumped type: \'b\'',
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
                'Cannot automatically resolve latte template from expression.',
                17,
                'SomeControl.php',
            ],
            [
                'Cannot automatically resolve latte template from expression.',
                23,
                'SomeControl.php',
            ],
        ]);
    }

    public function testResolve(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Resolve/SomeControl.php'], [
            [
                'Variable $nonExistingVariable might not be defined.',
                3,
                'constVar.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                3,
                'explicit.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                3,
                'defaultVars.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                3,
                'explicitVars.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                3,
                'defaultObject.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                3,
                'explicitObject.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                3,
                'complexType.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                1,
                'throwSometimes.latte',
            ],
        ]);
    }

    public function testBlocks(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Blocks/SomeControl.php'], [
            [
                'Dumped type: ?\stdClass',
                2,
                'define.latte',
            ],
            [
                'Dumped type: ?string',
                3,
                'define.latte',
            ],
            [
                'Dumped type: ?string',
                4,
                'define.latte',
            ],
            [
                'Dumped type: mixed',
                5,
                'define.latte',
            ],
            [
                'Dumped type: string',
                6,
                'define.latte',
            ],
            [
                'Dumped type: string',
                7,
                'define.latte',
            ],
        ]);
    }
}
