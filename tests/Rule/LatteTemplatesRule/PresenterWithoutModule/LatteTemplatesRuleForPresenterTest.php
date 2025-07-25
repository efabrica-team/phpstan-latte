<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule;

use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTestCase;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\LinksPresenter;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\VariablesPresenter;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source\CustomFormRenderer;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source\SomeControl;

final class LatteTemplatesRuleForPresenterTest extends LatteTemplatesRuleTestCase
{
    protected static function additionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/../../../config.neon',
            __DIR__ . '/config.neon',
            __DIR__ . '/mapping.neon',
        ];
    }

    public function testVariables(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/VariablesPresenter.php'], [
            [
                'Undefined variable: $items',
                5,
                'default.latte',
            ],
            [
                'Argument of an invalid type mixed supplied for foreach, only iterables are supported.',
                5,
                'default.latte',
            ],
            [
                'Cannot access property $title on mixed.',
                6,
                'default.latte',
            ],
            [
                'Cannot access property $id on mixed.',
                7,
                'default.latte',
            ],
            [
                'Cannot access property $title on mixed.',
                7,
                'default.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                18,
                'default.latte',
            ],
            [
                'Undefined variable: $fromOtherAction',
                19,
                'default.latte',
            ],
            [
                'Dumped type: array<array<string>>',
                21,
                'default.latte',
            ],
            [
                'Dumped type: array<string>',
                23,
                'default.latte',
            ],
            [
                'Dumped type: array<string>',
                31,
                'default.latte',
            ],
            [
                'Dumped type: string',
                37,
                'default.latte',
            ],
            [
                "Dumped type: array{'foo', 'bar', 'baz'}",
                45,
                'default.latte',
            ],
            [
                "Dumped type: 'bar'|'baz'|'foo'",
                47,
                'default.latte',
            ],
            [
                "Dumped type: 'bar'|'baz'|'foo'",
                56,
                'default.latte',
            ],
            [
                'Undefined variable: $overwritted',
                61,
                'default.latte',
            ],
            [
                'Undefined variable: $parentOverwritted',
                63,
                'default.latte',
            ],
            [
                'Undefined variable: $calledParentSecondOverwritted',
                68,
                'default.latte',
            ],
            [
                'Dumped type: ' . VariablesPresenter::class,
                71,
                'default.latte',
            ],
            [
                "Dumped type: 'first item'",
                73,
                'default.latte',
            ],
            [
                "Dumped type: 'second item'",
                74,
                'default.latte',
            ],
            [
                "Dumped type: 'first item'",
                75,
                'default.latte',
            ],
            [
                "Dumped type: 'second item'",
                76,
                'default.latte',
            ],
            [
                'Dumped type: mixed',
                77,
                'default.latte',
            ],
            [
                'Dumped type: mixed',
                78,
                'default.latte',
            ],
            [
                'Dumped type: string',
                79,
                'default.latte',
            ],
            [
                'Dumped type: int',
                80,
                'default.latte',
            ],
            [
                'Dumped type: mixed',
                81,
                'default.latte',
            ],
            [
                "Dumped type: array<'bar'|'baz'|'foo'>", // False positive - TODO create DynamicMethodReturnTypeExtension for Latte\Runtime\Filters::slice - inspire in ArraySliceFunctionReturnTypeExtension and SubstrDynamicReturnTypeExtension
                92,
                'default.latte',
            ],
            [
                'Dumped type: string',
                94,
                'default.latte',
            ],
            [
                'Dumped type: \'encapsed variable\'',
                96,
                'default.latte',
            ],
            [
                'Dumped type: \'default value\'',
                97,
                'default.latte',
            ],
            [
                'Dumped type: \'value from presenter\'',
                98,
                'default.latte',
            ],
            [
                'Undefined variable: $fromRenderDefault',
                8,
                'other.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                9,
                'other.latte',
            ],
            [
                'Cannot resolve included latte template.',
                15,
                'other.latte',
            ],
            [
                'Undefined variable: $fromRenderDefault',
                3,
                '@partial.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                4,
                '@partial.latte',
            ],
            [
                'Undefined variable: $fromRenderDefault',
                4,
                '@subpartial.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                5,
                '@subpartial.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                4,
                'parent.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                5,
                'noAction.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                5,
                'direct.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                1,
                '@includedDynamically.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                4,
                'onlyRender.latte',
            ],
            [
                'Undefined variable: $fromDifferentRenderAction', // action different
                3,
                'different.latte',
            ],
            [
                'Undefined variable: $fromDifferentRendersAction', // action different
                4,
                'different.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable', // action different
                6,
                'different.latte',
            ],
            [
                'Undefined variable: $fromDifferentRenderAction', // action differentRenders
                3,
                'different.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable', // action differentRenders
                6,
                'different.latte',
            ],
            [
                'Undefined variable: $fromDifferentRendersAction', // action differentRender
                4,
                'different.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable', // action differentRender
                6,
                'different.latte',
            ],
            [
                'Undefined variable: $fromDifferentRenderAction', // action differentRenderConditional
                3,
                'different.latte',
            ],
            [
                'Undefined variable: $fromDifferentRendersAction', // action differentRenderConditional
                4,
                'different.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable', // action differentRenderConditional
                6,
                'different.latte',
            ],
            [
                'Undefined variable: $fromDifferentRenderAction', // action differentRenderIndirect
                3,
                'different.latte',
            ],
            [
                'Undefined variable: $fromDifferentRendersAction', // action differentRenderIndirect
                4,
                'different.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable', // action differentRenderIndirect
                6,
                'different.latte',
            ],
            [
                'Undefined variable: $fromDifferentRendersAction', // action different2
                3,
                'different2.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable', // action different2
                5,
                'different2.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable', // action differentRenders
                5,
                'different2.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable', // action differentRenders
                3,
                'differentRenderConditional.latte',
            ],
            [
                'Dumped type: string',
                3,
                'arrayShapeParams.latte',
            ],
            [
                'Variable $a might not be defined.',
                3,
                'arrayShapeParams.latte',
            ],
            [
                'Dumped type: int',
                4,
                'arrayShapeParams.latte',
            ],
            [
                'Dumped type: string|null',
                5,
                'arrayShapeParams.latte',
            ],
            [
                'Dumped type: object{a?: string, b: int, c: string|null}&stdClass',
                3,
                'objectShapeParams.latte',
            ],
            [
                'Dumped type: mixed',
                4,
                'objectShapeParams.latte',
            ],
            [
                'Dumped type: int',
                5,
                'objectShapeParams.latte',
            ],
            [
                'Dumped type: string|null',
                6,
                'objectShapeParams.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $otherTitle',
                7,
                '@layoutOther.latte',
            ],
        ]);
    }

    public function testComponents(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ComponentsPresenter.php'], [
            [
                'Component with name "onlyCreateForm" probably doesn\'t exist.',
                9,
                'default.latte',
            ],
            [
                'Component with name "nonExistingControl" probably doesn\'t exist.',
                11,
                'default.latte',
            ],
            [
                'Call to an undefined method ' . SomeControl::class . '::renderNonExistingRender().',
                17,
                'default.latte',
            ],
            [
                'Component with name "someControl-nonexisting" probably doesn\'t exist.',
                23,
                'default.latte',
            ],
            [
                'Component with name "noType" have no type specified.',
                25,
                'default.latte',
                'Define return type of createComponentNoType method.',
            ],
            [
                'Component with name "onlyParentDefaultForm" probably doesn\'t exist.',
                7,
                'create.latte',
            ],
            [
                'Component with name "nonExistingControl" probably doesn\'t exist.',
                11,
                'create.latte',
            ],
            [
                'Component with name "someControl" probably doesn\'t exist.',
                13,
                'create.latte',
            ],
            [
                'Component with name "someControl-header" probably doesn\'t exist.',
                14,
                'create.latte',
            ],
            [
                'Component with name "someControl-body" probably doesn\'t exist.',
                15,
                'create.latte',
            ],
            [
                'Component with name "someControl-body-table" probably doesn\'t exist.',
                16,
                'create.latte',
            ],
            [
                'Component with name "someControl-footer" probably doesn\'t exist.',
                17,
                'create.latte',
            ],
            [
                'Component with name "someControl-nonexisting" probably doesn\'t exist.',
                18,
                'create.latte',
            ],
            [
                'Component with name "nonExistingControl" probably doesn\'t exist.',
                3,
                'parent.latte',
            ],
            [
                'Component with name "onlyParentDefaultForm" probably doesn\'t exist.',
                7,
                'noAction.latte',
            ],
            [
                'Component with name "onlyCreateForm" probably doesn\'t exist.',
                9,
                'noAction.latte',
            ],
            [
                'Component with name "nonExistingControl" probably doesn\'t exist.',
                11,
                'noAction.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
        ]);
    }

    public function testForms(): void
    {
        $expectedErrors = [
            [
                'Form control with name "password" probably does not exist.',
                4,
                'default.latte',
            ],
            [
                'Method Nette\Forms\Controls\BaseControl::getControlPart() invoked with 1 parameter, 0 required.',
                11,
                'default.latte',
            ],
            [
                'Method Nette\Forms\Controls\BaseControl::getLabelPart() invoked with 1 parameter, 0 required.',
                11,
                'default.latte',
            ],
            [
                'Form control with name "second_submit" probably does not exist.',
                15,
                'default.latte',
            ],
            [
                'Form control with name "second_submit_label" probably does not exist.',
                15,
                'default.latte',
            ],
            [
                'Method Nette\Forms\Rendering\DefaultFormRenderer::renderControls() invoked with 2 parameters, 1 required.',
                19,
                'default.latte',
            ],
            [
                'Parameter #1 $parent of method Nette\Forms\Rendering\DefaultFormRenderer::renderControls() expects Nette\Forms\Container|Nette\Forms\ControlGroup, null given.',
                20,
                'default.latte',
            ],
            [
                'Call to an undefined method ' . CustomFormRenderer::class . '::someNonExistingCustomMethod().',
                24,
                'default.latte',
            ],
            [
                'Form with name "notExisting" probably does not exist.',
                LatteVersion::isLatte3() ? 48 : 45,
                'default.latte',
            ],
            [
                'Form control with name "username" probably does not exist.',
                49,
                'default.latte',
            ],
            [
                'Form control with name "5" probably does not exist.',
                87,
                'default.latte',
            ],
            [
                'Form control with name "5" probably does not exist.',
                91,
                'default.latte',
            ],
            [
                'Form control with name "1" probably does not exist.',
                105,
                'default.latte',
            ],
            [
                'Form control with name "1" probably does not exist.',
                109,
                'default.latte',
            ],
            [
                'Call to an undefined method ' . CustomFormRenderer::class . '::someNonExistingCustomMethod().',
                130,
                'default.latte',
            ],
            [
                'Option "item4" for control "checkbox_list" probably doesn\'t exist.',
                137,
                'default.latte',
            ],
            [
                'Option "4" for control "radio_list" probably doesn\'t exist.',
                141,
                'default.latte',
            ],
            [
                'Dumped type: Nette\Forms\Controls\TextInput',
                142,
                'default.latte',
            ],
            [
                'Dumped type: Nette\Forms\Controls\TextInput',
                144,
                'default.latte',
            ],
            [
                'Form control with name "2" probably does not exist.',
                164,
                'default.latte',
            ],
            [
                'Form control with name "10" probably does not exist.',
                169,
                'default.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
        ];
        if (LatteVersion::isLatte3()) {
            $expectedErrors[] = [
                'PHPDoc tag @var with type Nette\Forms\Controls\BaseControl is not subtype of type Nette\Forms\Controls\TextArea.', // Should be removed after issue https://github.com/efabrica-team/phpstan-latte/issues/444 is resolved
                55,
                'default.latte',
            ];
        }
        $this->analyse([__DIR__ . '/Fixtures/FormsPresenter.php'], $expectedErrors);
    }

    public function testFilters(): void
    {
        $expectedErrors = [
            [
                'Function strlen invoked with 3 parameters, 1 required.',
                3,
                'default.latte',
            ],
            [
                'Parameter #1 $string of function strlen expects string, int given.',
                3,
                'default.latte',
            ],
            [
                'Trying to invoke mixed but it\'s not a callable.',
                5,
                'default.latte',
            ],
            [
                'Undefined latte filter "nonExistingFilter".',
                5,
                'default.latte',
                'Register it in phpstan.neon: parameters > latte > filters. See https://github.com/efabrica-team/phpstan-latte/blob/main/docs/configuration.md#filters',
            ],
            [
                'Closure invoked with 1 parameter, 2 required.',
                7,
                'default.latte',
            ],
            [
                'Parameter #1 of closure expects string, int given.',
                8,
                'default.latte',
            ],
            [
                'Parameter #2 of closure expects int, string given.',
                8,
                'default.latte',
            ],
            [
                'Closure invoked with 1 parameter, 2 required.',
                10,
                'default.latte',
            ],
            [
                'Parameter #1 of closure expects string, int given.',
                11,
                'default.latte',
            ],
            [
                'Parameter #2 of closure expects int, string given.',
                11,
                'default.latte',
            ],
            [
                'Callable callable(string, int): string invoked with 1 parameter, 2 required.',
                13,
                'default.latte',
            ],
            [
                'Parameter #1 of callable callable(string, int): string expects string, int given.',
                14,
                'default.latte',
            ],
            [
                'Parameter #2 of callable callable(string, int): string expects int, string given.',
                14,
                'default.latte',
            ],
            [
                'Trying to invoke mixed but it\'s not a callable.',
                4,
                'parent.latte',
            ],
            [
                'Undefined latte filter "actionDefaultFilter".',
                4,
                'parent.latte',
                'Register it in phpstan.neon: parameters > latte > filters. See https://github.com/efabrica-team/phpstan-latte/blob/main/docs/configuration.md#filters',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Cannot convert array<mixed>|string to HTML string.',
                22,
                'default.latte',
            ],
        ];

        if (LatteVersion::isLatte3()) {
            $expectedErrors[] = [
                'Unexpected \'|\' (on line 2 at column 6)',
                2,
                'translate_new.latte',
            ];
            $expectedErrors[] = [
                'Parameter #1 $s of static method Latte\Essential\Filters::lower() expects bool|float|int|string|Stringable|null, stdClass given.',
                19,
                'default.latte',
            ];
            $expectedErrors[] = [
                'Parameter #1 $s of static method Latte\Essential\Filters::upper() expects bool|float|int|string|Stringable|null, stdClass given.',
                20,
                'default.latte',
            ];
            $expectedErrors[] = [
                'Parameter #1 $s of static method Latte\Essential\Filters::capitalize() expects bool|float|int|string|Stringable|null, stdClass given.',
                21,
                'default.latte',
            ];
            $expectedErrors[] = [
                'Parameter #1 $value of static method Latte\Essential\Filters::slice() expects array<mixed>|string, string|null given.',
                22,
                'default.latte',
            ];
            $expectedErrors[] = [
                'Unable to resolve the template type T in call to method static method Latte\Essential\Filters::slice()',
                22,
                'default.latte',
                'See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type',
            ];
        } else {
            $expectedErrors[] = [
                'Syntax error, unexpected \')\'',
                -1,
                'translate_new.latte',
            ];

            $filterStringType = 'bool|float|int|';
            if (PHP_VERSION_ID < 80000) {
                $filterStringType .= 'Latte\Runtime\HtmlStringable|Nette\HtmlStringable|';
            }
            $filterStringType .= 'string|Stringable|null';

            $expectedErrors[] = [
                'Parameter #1 $s of static method Latte\Runtime\Filters::lower() expects ' . $filterStringType . ', stdClass given.',
                19,
                'default.latte',
            ];
            $expectedErrors[] = [
                'Parameter #1 $s of static method Latte\Runtime\Filters::upper() expects ' . $filterStringType . ', stdClass given.',
                20,
                'default.latte',
            ];
            $expectedErrors[] = [
                'Parameter #1 $s of static method Latte\Runtime\Filters::capitalize() expects ' . $filterStringType . ', stdClass given.',
                21,
                'default.latte',
            ];
            $expectedErrors[] = [
                'Parameter #1 $value of static method Latte\Runtime\Filters::slice() expects array<mixed>|string, string|null given.',
                22,
                'default.latte',
            ];
            $expectedErrors[] = [
                'Unable to resolve the template type T in call to method static method Latte\Runtime\Filters::slice()',
                22,
                'default.latte',
                'See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type',
            ];
        }

        $this->analyse([__DIR__ . '/Fixtures/FiltersPresenter.php'], $expectedErrors);
    }

    public function testLinks(): void
    {
        $expectedErrors = [
            [
                'Method ' . LinksPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                7,
                'default.latte',
            ],
            [
                'Method ' . LinksPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                8,
                'default.latte',
            ],
            [
                'Method ' . LinksPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                10,
                'default.latte',
            ],
            [
                'Method ' . LinksPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                11,
                'default.latte',
            ],
            [
                'Parameter #1 $id of method ' . LinksPresenter::class . '::actionEdit() expects string, null given.',
                20,
                'default.latte',
            ],
            [
                'Parameter #1 $id of method ' . LinksPresenter::class . '::actionEdit() expects string, null given.',
                21,
                'default.latte',
            ],
            [
                'Parameter #1 $id of method ' . LinksPresenter::class . '::actionEdit() expects string, array<string, string> given.',
                26,
                'default.latte',
            ],
            [
                'Parameter #1 $id of method ' . LinksPresenter::class . '::actionEdit() expects string, array<string, string> given.',
                27,
                'default.latte',
            ],
            [
                'Parameter #1 $id of method ' . LinksPresenter::class . '::actionEdit() expects string, array<string, int|string> given.',
                38,
                'default.latte',
            ],
            [
                'Parameter #1 $id of method ' . LinksPresenter::class . '::actionEdit() expects string, array<string, int|string> given.',
                39,
                'default.latte',
            ],
            [
                'Parameter #1 $id of method ' . LinksPresenter::class . '::actionEdit() expects string, null given.',
                65,
                'default.latte',
            ],
            [
                'Parameter #1 $id of method ' . LinksPresenter::class . '::actionEdit() expects string, array<string, int> given.',
                66,
                'default.latte',
            ],
            [
                'Parameter #1 $id of method ' . LinksPresenter::class . '::actionEdit() expects string, null given.',
                67,
                'default.latte',
            ],
            [
                'Parameter #1 $id of method ' . LinksPresenter::class . '::actionEdit() expects string, null given.',
                69,
                'default.latte',
            ],
            [
                'Parameter #2 $sorting of method ' . LinksPresenter::class . '::actionEdit() expects int, string given.',
                69,
                'default.latte',
            ],
            [
                'Parameter #1 $id of method ' . LinksPresenter::class . '::actionEdit() expects string, array<string, string> given.',
                70,
                'default.latte',
            ],
            [
                'Parameter #1 $id of method ' . LinksPresenter::class . '::actionEdit() expects string, null given.',
                71,
                'default.latte',
            ],
            [
                'Parameter #2 $sorting of method ' . LinksPresenter::class . '::actionEdit() expects int, string given.',
                71,
                'default.latte',
            ],
            [
                'Cannot load presenter \'Links:Invalid\', class \'Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\LinksModule\InvalidPresenter\' was not found.',
                73,
                'default.latte',
                'Check if your PHPStan configuration for latte > applicationMapping is correct. See https://github.com/efabrica-team/phpstan-latte/docs/configuration.md#applicationmapping',
            ],
            [
                'Parameter #2 $param2 of method ' . LinksPresenter::class . '::renderParamsMismatch() expects string, null given.',
                75,
                'default.latte',
            ],
            [
                'Invalid link: Unable to pass parameters to "' . LinksPresenter::class . '::nonExistingMethod()", missing corresponding method.',
                94,
                'default.latte',
                'Add method actionNonExistingMethod or renderNonExistingMethod with corresponding parameters to presenter Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\LinksPresenter',
            ],
            [
                'Parameter #1 $id of method ' . LinksPresenter::class . '::handleDelete() expects string, null given.',
                97,
                'default.latte',
            ],
            [
                'Method ' . LinksPresenter::class . '::handleDelete() invoked with 2 parameters, 1 required.',
                98,
                'default.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
        ];
        $this->analyse([__DIR__ . '/Fixtures/LinksPresenter.php'], $expectedErrors);
    }

    public function testRecursion(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/RecursionPresenter.php'], [
            [
                'Dumped type: 10',
                1,
                'recursion.latte',
            ],
            [
                'Dumped type: 9',
                1,
                'recursion.latte',
            ],
            [
                'Dumped type: 8',
                1,
                'recursion.latte',
            ],
            [
                'Dumped type: 7',
                1,
                'recursion.latte',
            ],
            [
                'Comparison operation ">" between 10 and 0 is always true.',
                2,
                'recursion.latte',
            ],
            [
                'Comparison operation ">" between 9 and 0 is always true.',
                2,
                'recursion.latte',
            ],
            [
                'Comparison operation ">" between 8 and 0 is always true.',
                2,
                'recursion.latte',
            ],
            [
                'Comparison operation ">" between 7 and 0 is always true.',
                2,
                'recursion.latte',
            ],
            [
                'Dumped type: 10',
                1,
                'indirectRecursion.latte',
            ],
            [
                'Dumped type: 9',
                1,
                '@indirectRecursionB.latte',
            ],
            [
                'Dumped type: 8',
                1,
                'indirectRecursion.latte',
            ],
            [
                'Dumped type: 7',
                1,
                '@indirectRecursionB.latte',
            ],
            [
                'Dumped type: 6',
                1,
                'indirectRecursion.latte',
            ],
            [
                'Dumped type: 5',
                1,
                '@indirectRecursionB.latte',
            ],
            [
                'Dumped type: 4',
                1,
                'indirectRecursion.latte',
            ],
            [
                'Dumped type: 3',
                1,
                '@indirectRecursionB.latte',
            ],
            [
                'Comparison operation ">" between 10 and 0 is always true.',
                2,
                'indirectRecursion.latte',
            ],
            [
                'Comparison operation ">" between 8 and 0 is always true.',
                2,
                'indirectRecursion.latte',
            ],
            [
                'Comparison operation ">" between 6 and 0 is always true.',
                2,
                'indirectRecursion.latte',
            ],
            [
                'Comparison operation ">" between 4 and 0 is always true.',
                2,
                'indirectRecursion.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
        ]);
    }

    public function testResolve(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ResolvePresenter.php'], [
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'dieSometimes.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'empty.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'exitSometimes.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'recursion.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'throwSometimes.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'setFile.changed.latte',
            ],
            [
                'Cannot resolve rendered latte template.',
                113,
                'ResolvePresenter.php',
            ],
            [
                'Cannot automatically resolve template used by sendTemplate().',
                119,
                'ResolvePresenter.php',
            ],
            [
                'Undefined variable: $nonExistingVariable',
                3,
                'sendTemplate.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Component with name "header" probably doesn\'t exist.',
                11,
                '@layout.latte',
            ],
            [
                'Component with name "header" probably doesn\'t exist.',
                11,
                '@layout.latte',
            ],
            [
                'Component with name "header" probably doesn\'t exist.',
                11,
                '@layout.latte',
            ],
            [
                'Component with name "header" probably doesn\'t exist.',
                11,
                '@layout.latte',
            ],
            [
                'Component with name "header" probably doesn\'t exist.',
                11,
                '@layout.latte',
            ],
            [
                'Component with name "header" probably doesn\'t exist.',
                11,
                '@layout.latte',
            ],
            [
                'Component with name "header" probably doesn\'t exist.',
                11,
                '@layout.latte',
            ],
            [
                'Component with name "header" probably doesn\'t exist.',
                11,
                '@layout.latte',
            ],
            [
                'Component with name "footer" probably doesn\'t exist.',
                13,
                '@layout.latte',
            ],
            [
                'Component with name "footer" probably doesn\'t exist.',
                13,
                '@layout.latte',
            ],
            [
                'Component with name "footer" probably doesn\'t exist.',
                13,
                '@layout.latte',
            ],
            [
                'Component with name "footer" probably doesn\'t exist.',
                13,
                '@layout.latte',
            ],
            [
                'Component with name "footer" probably doesn\'t exist.',
                13,
                '@layout.latte',
            ],
            [
                'Component with name "footer" probably doesn\'t exist.',
                13,
                '@layout.latte',
            ],
            [
                'Component with name "footer" probably doesn\'t exist.',
                13,
                '@layout.latte',
            ],
            [
                'Component with name "footer" probably doesn\'t exist.',
                13,
                '@layout.latte',
            ],
        ]);
    }

    public function testTrait(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/TraitPresenter.php'], [
            [
                'Dumped type: \'foo\'',
                3,
                'trait.latte',
            ],
            [
                'Dumped type: array{\'foo\', \'bar\', \'baz\'}',
                4,
                'trait.latte',
            ],
            [
                'Dumped type: array<int>',
                5,
                'trait.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Component with name "header" probably doesn\'t exist.',
                11,
                '@layout.latte',
            ],
            [
                'Component with name "header" probably doesn\'t exist.',
                11,
                '@layout.latte',
            ],
            [
                'Component with name "footer" probably doesn\'t exist.',
                13,
                '@layout.latte',
            ],
            [
                'Component with name "footer" probably doesn\'t exist.',
                13,
                '@layout.latte',
            ],
        ]);
    }

    public function testStartupView(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/StartupViewPresenter.php'], [
            [
                'Undefined variable: $nonExistingVariable', // action default
                4,
                'default.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable', // action parent
                4,
                'parent.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable', // action startup
                4,
                'startup.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable', // action default(startup)
                4,
                'startup.latte',
            ],
            [
                'Undefined variable: $nonExistingVariable', // action parent(startup)
                4,
                'startup.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
        ]);
    }

    public function testSnippets(): void
    {
        $expectedErrors = [
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $title',
                7,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Undefined variable: $neverDefined',
                10,
                '@layout.latte',
            ],
            [
                'Component with name "header" probably doesn\'t exist.',
                11,
                '@layout.latte',
            ],
            [
                'Component with name "header" probably doesn\'t exist.',
                11,
                '@layout.latte',
            ],
            [
                'Component with name "footer" probably doesn\'t exist.',
                13,
                '@layout.latte',
            ],
            [
                'Component with name "footer" probably doesn\'t exist.',
                13,
                '@layout.latte',
            ],
        ];
        if (LatteVersion::isLatte3()) {
            $expectedErrors[] = [
                'Combination of n:snippet with n:foreach is invalid, use n:inner-foreach (on line 3 at column 6)',
                3,
                'compileError.latte',
            ];
        } else {
            $expectedErrors[] = [
                'Combination of n:snippet with n:foreach is invalid, use n:inner-foreach (on line 3)',
                3,
                'compileError.latte',
            ];
        }
        $this->analyse([__DIR__ . '/Fixtures/SnippetsPresenter.php'], $expectedErrors);
    }
}
