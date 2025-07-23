<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTest;

final class LatteTemplatesRuleForSimpleControlTest extends LatteTemplatesRuleTest
{
    protected static function additionalConfigFiles(): array
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
                'Dumped type: *ERROR*',
                3,
                'default.latte',
            ],
            [
                'Undefined variable: $c',
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
                'Dumped type: *ERROR*',
                3,
                'default.latte',
            ],
            [
                'Undefined variable: $c',
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
                'Dumped type: *ERROR*',
                3,
                'default.latte',
            ],
            [
                'Undefined variable: $c',
                3,
                'default.latte',
            ],
        ]);
    }

    public function testMultipleRenderMethods(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/MultipleRenderMethods/SomeControl.php'], [
            [
                'Undefined variable: $c',
                3,
                'default.latte',
            ],
            [
                'Undefined variable: $a',
                1,
                'test.latte',
            ],
            [
                'Undefined variable: $b',
                2,
                'test.latte',
            ],
            [
                'Undefined variable: $b',
                2,
                'param_a.latte',
            ],
            [
                'Undefined variable: $b',
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
                'Undefined variable: $nonExistingVariable',
                3,
                'constVar.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'explicit.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'defaultVars.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'explicitVars.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'defaultObject.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'explicitObject.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'complexType.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                1,
                'throwSometimes.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'engine.latte',
            ],
            [
                'Rendered latte template ' . __DIR__ . '/Fixtures/Resolve/error.latte does not exist.',
                196,
                'SomeControl.php',
            ],
        ]);
    }

    public function testBlocks(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Blocks/SomeControl.php'], [
            [
                'Block my-block has parameter $paramArrayMixedDefault with no value type specified in iterable type array.',
                1,
                'define.latte',
                'See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type',
            ],
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
                'Dumped type: array',
                11,
                'define.latte',
            ],
            [
                'Dumped type: bool',
                12,
                'define.latte',
            ],
            [
                'Dumped type: bool',
                13,
                'define.latte',
            ],
            [
                'Dumped type: string|null',
                14,
                'define.latte',
            ],
            [
                'Dumped type: string',
                15,
                'define.latte',
            ],
            [
                'Block no-comma-block has parameter $name with no type specified.',
                18,
                'define.latte',
            ],
            [
                'Block no-comma-block has parameter $class with no type specified.',
                18,
                'define.latte',
            ],
            [
                'Dumped type: mixed',
                19,
                'define.latte',
            ],
            [
                'Dumped type: mixed',
                20,
                'define.latte',
            ],
            [
                'Block my-block invoked with 2 parameters, 4-14 required.',
                24,
                'define.latte',
            ],
            [
                'Parameter #1 $paramObject of block my-block expects stdClass, string given.',
                24,
                'define.latte',
            ],
            [
                'Parameter #2 $paramString of block my-block expects string, int given.',
                24,
                'define.latte',
            ],
            [
                'Parameter #1 $paramObject of block my-block expects stdClass, string given.',
                25,
                'define.latte',
            ],
            [
                'Parameter #2 $paramString of block my-block expects string, int given.',
                25,
                'define.latte',
            ],
            [
                'Parameter #3 $paramNullable of block my-block expects string|null, none given.',
                25,
                'define.latte',
            ],
            [
                'Block my-block invoked with 0 parameters, 4-14 required.',
                26,
                'define.latte',
            ],
            [
                'Block no-comma-block invoked with 1 parameter, 2 required.',
                29,
                'define.latte',
            ],
            [
                'Block no-comma-block invoked with 0 parameters, 2 required.',
                30,
                'define.latte',
            ],
            [
                'Dumped type: int',
                35,
                'define.latte',
            ],
            [
                'Dumped type: string',
                36,
                'define.latte',
            ],
            [
                'Dumped type: \'some string\'',
                39,
                'define.latte',
            ],
        ]);
    }

    public function testVariables(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Variables/SomeControl.php'], [
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'default.latte',
            ],
            [
                'Dumped type: \'default value\'',
                14,
                'default.latte',
            ],
            [
                'Cannot convert stdClass to HTML string.',
                16,
                'default.latte',
            ],
        ]);
    }

    public function testLinks(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Links/SomeControl.php'], [
            [
                'Parameter #1 $id of method Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\Links\SomeControl::handleDelete() expects int, null given.',
                2,
                'default.latte',
            ],
            [
                'Parameter #1 $id of method Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\Links\SomeControl::handleDelete() expects int, string given.',
                3,
                'default.latte',
            ],
        ]);
    }

    public function testHierarchy(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Hierarchy/SomeControl.php'], [
            [
                'Undefined variable: $nonExistingVariable',
                1,
                'default.latte',
            ],
            [
                'Dumped type: string',
                11,
                'default.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                1,
                'parent.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
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
                'Dumped type: *ERROR*',
                3,
                'base.latte',
            ],
            [
                'Undefined variable: $c',
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
                'Dumped type: *ERROR*',
                3,
                'trait.latte',
            ],
            [
                'Undefined variable: $c',
                3,
                'trait.latte',
            ],
            ]
        );
    }

    public function testTraitVariables(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/TraitVariables/SomeControl.php'], [
            [
                'Dumped type: \'baseA\'',
                1,
                'default.latte',
            ],
            [
                'Dumped type: \'baseB\'',
                2,
                'default.latte',
            ],
            [
                'Dumped type: int',
                3,
                'default.latte',
            ],
        ]);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testEnums(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Enums/SomeControl.php', __DIR__ . '/Source/EnumSomething.php'], [
            [
                'Cannot convert Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Source\EnumSomething::Foo to HTML string.',
                1,
                'default.latte',
            ],
            [
                'Cannot convert Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Source\EnumSomething::Foo to HTML string.',
                3,
                'default.latte',
            ],
            [
                'Access to undefined constant Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Source\EnumSomething::Bar.',
                5,
                'default.latte',
            ],
            [
                'Access to undefined constant Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Source\EnumSomething::Bar.',
                6,
                'default.latte',
            ],
            [
                'Cannot access property $value on mixed.',
                6,
                'default.latte',
            ],
        ]);
    }
}
