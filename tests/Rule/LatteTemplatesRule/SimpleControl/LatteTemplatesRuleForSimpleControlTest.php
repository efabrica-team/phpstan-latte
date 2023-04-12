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
            __DIR__ . '/config.neon',
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
                'Rendered latte template ' . __DIR__ . '/Fixtures/MultipleRenderMethods/invalid_file.latte does not exist.',
                41,
                'SomeControl.php',
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
                'Cannot resolve rendered latte template.',
                17,
                'SomeControl.php',
            ],
            [
                'Cannot resolve latte template for SomeControl::renderNotEvaluatedVar().',
                20,
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
                'Included latte template ' . __DIR__ . '/Fixtures/Resolve/not-existing.latte does not exist.',
                2,
                'default.latte',
            ],

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
            [
                'Rendered latte template ' . __DIR__ . '/Fixtures/Resolve/error.latte does not exist.',
                193,
                'SomeControl.php',
            ],
        ]);
    }

    public function testBlocks(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Blocks/SomeControl.php'], [
            [
                'Block my-block has parameter $paramNoType with no type specified.',
                1,
                'define.latte',
            ],
            [
                'Block my-block has parameter $paramNoTypeDefault with no type specified.',
                1,
                'define.latte',
            ],
            [
                'Dumped type: stdClass',
                2,
                'define.latte',
            ],
            [
                'Dumped type: string',
                3,
                'define.latte',
            ],
            [
                'Dumped type: string|null',
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
                'Dumped type: mixed',
                7,
                'define.latte',
            ],
            [
                'Dumped type: float',
                8,
                'define.latte',
            ],
            [
                'Dumped type: int',
                9,
                'define.latte',
            ],
            [
                'Dumped type: array<stdClass>',
                10,
                'define.latte',
            ],
            [
                'Block my-block invoked with 2 parameters, 4-9 required.',
                14,
                'define.latte',
            ],
            [
                'Parameter #1 $paramObject of block my-block expects stdClass, string given.',
                14,
                'define.latte',
            ],
            [
                'Parameter #2 $paramString of block my-block expects string, int given.',
                14,
                'define.latte',
            ],
            [
                'Parameter #1 $paramObject of block my-block expects stdClass, string given.',
                15,
                'define.latte',
            ],
            [
                'Parameter #2 $paramString of block my-block expects string, int given.',
                15,
                'define.latte',
            ],
            [
                'Parameter #3 $paramNullable of block my-block expects string|null, none given.',
                15,
                'define.latte',
            ],
            [
                'Block my-block invoked with 0 parameters, 4-9 required.',
                16,
                'define.latte',
            ],
            [
                'Dumped type: \'some string\'',
                18,
                'define.latte',
            ],
        ]);
    }

    public function testVariables(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Variables/SomeControl.php'], [
            [
                'Variable $nonExistingVariable might not be defined.',
                3,
                'default.latte',
            ],
            [
                'Dumped type: \'default value\'',
                14,
                'default.latte',
            ],
        ]);
    }

    public function testHierarchy(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Hierarchy/SomeControl.php'], [
            [
                'Variable $nonExistingVariable might not be defined.',
                1,
                'default.latte',
            ],
            [
                'Dumped type: string',
                11,
                'default.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                1,
                'parent.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                1,
                'grandParent.latte',
            ],
        ]);
    }

    public function testTraitOverriding(): void
    {
        $this->analyse(
            [
                __DIR__ . '/Fixtures/TraitOverriding/SomeControl.php',
                __DIR__ . '/Fixtures/TraitOverriding/SomeControlWithTemplatePathBehavior.php',
                __DIR__ . '/Fixtures/TraitOverriding/BaseControl.php',
            ],
            [
            [
                'Dumped type: \'a\'',
                1,
                'base.latte',
            ],
            [
                'Dumped type: \'b\'',
                2,
                'base.latte',
            ],
            [
                'Dumped type: mixed',
                3,
                'base.latte',
            ],
            [
                'Variable $c might not be defined.',
                3,
                'base.latte',
            ],
            [
                'Dumped type: \'c\'',
                1,
                'trait.latte',
            ],
            [
                'Dumped type: \'d\'',
                2,
                'trait.latte',
            ],
            [
                'Dumped type: mixed',
                3,
                'trait.latte',
            ],
            [
                'Variable $c might not be defined.',
                3,
                'trait.latte',
            ],
            ]
        );
    }
}
