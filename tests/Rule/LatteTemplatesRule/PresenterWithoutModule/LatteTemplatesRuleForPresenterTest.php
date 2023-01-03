<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTest;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\LinksPresenter;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\ResolvePresenter;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source\CustomFormRenderer;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source\SomeControl;

final class LatteTemplatesRuleForPresenterTest extends LatteTemplatesRuleTest
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/../../../config.neon',
            __DIR__ . '/config.neon',
        ];
    }

    public function testVariables(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/VariablesPresenter.php'], [
            [
                'Variable $items might not be defined.',
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
                'Variable $nonExistingVariable might not be defined.',
                18,
                'default.latte',
            ],
            [
                'Variable $fromOtherAction might not be defined.',
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
                'Variable $overwritted might not be defined.',
                61,
                'default.latte',
            ],
            [
                'Variable $parentOverwritted might not be defined.',
                63,
                'default.latte',
            ],
            [
                'Variable $fromRenderDefault might not be defined.',
                4,
                'other.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                5,
                'other.latte',
            ],
            [
                'Variable $fromRenderDefault might not be defined.',
                3,
                '@partial.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                4,
                '@partial.latte',
            ],
            [
                'Variable $fromRenderDefault might not be defined.',
                4,
                '@subpartial.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                5,
                '@subpartial.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                4,
                'parent.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                5,
                'noAction.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                5,
                'direct.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                1,
                '@includedDynamically.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                4,
                'onlyRender.latte',
            ],
            [
                'Variable $fromDifferentRenderAction might not be defined.', // action different
                3,
                'different.latte',
            ],
            [
                'Variable $fromDifferentRendersAction might not be defined.', // action different
                4,
                'different.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.', // action different
                6,
                'different.latte',
            ],
            [
                'Variable $fromDifferentRenderAction might not be defined.', // action differentRenders
                3,
                'different.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.', // action differentRenders
                6,
                'different.latte',
            ],
            [
                'Variable $fromDifferentRendersAction might not be defined.', // action differentRender
                4,
                'different.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.', // action differentRender
                6,
                'different.latte',
            ],
            [
                'Variable $fromDifferentRenderAction might not be defined.', // action differentRenderConditional
                3,
                'different.latte',
            ],
            [
                'Variable $fromDifferentRendersAction might not be defined.', // action differentRenderConditional
                4,
                'different.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.', // action differentRenderConditional
                6,
                'different.latte',
            ],
            [
                'Variable $fromDifferentRendersAction might not be defined.', // action different2
                3,
                'different2.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.', // action different2
                5,
                'different2.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.', // action differentRenders
                5,
                'different2.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.', // action differentRenders
                3,
                'differentRenderConditional.latte',
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
        ]);
    }

    public function testForms(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/FormsPresenter.php'], [
            [
                'Form field with name "password" probably does not exist.',
                4,
                'default.latte',
            ],
            [
                'Form field with name "second_submit" probably does not exist.',
                13,
                'default.latte',
            ],
            [
                'Form field with name "second_submit_label" probably does not exist.',
                13,
                'default.latte',
            ],
            [
                'Call to an undefined method ' . CustomFormRenderer::class . '::someNonExistingCustomMethod().',
                20,
                'default.latte',
            ],
        ]);
    }

    public function testFilters(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/FiltersPresenter.php'], [
            [
                'Function strlen invoked with 3 parameters, 1 required.',
                2,
                'default.latte',
            ],
            [
                'Parameter #1 $string of function strlen expects string, int given.',
                2,
                'default.latte',
            ],
            [
                'Trying to invoke mixed but it\'s not a callable.',
                4,
                'default.latte',
            ],
            [
                'Undefined latte filter "nonExistingFilter".',
                4,
                'default.latte',
                'Register it in phpstan.neon: parameters > latte > filters. See https://github.com/efabrica-team/phpstan-latte/docs/configuration.md#filters',
            ],
            [
                'Closure invoked with 1 parameter, 2 required.',
                6,
                'default.latte',
            ],
            [
                'Parameter #1 $ of closure expects string, int given.',
                7,
                'default.latte',
            ],
            [
                'Parameter #2 $ of closure expects int, string given.',
                7,
                'default.latte',
            ],
            [
                'Closure invoked with 1 parameter, 2 required.',
                9,
                'default.latte',
            ],
            [
                'Parameter #1 $ of closure expects string, int given.',
                10,
                'default.latte',
            ],
            [
                'Parameter #2 $ of closure expects int, string given.',
                10,
                'default.latte',
            ],
            [
                'Callable callable(string, int): string invoked with 1 parameter, 2 required.',
                12,
                'default.latte',
            ],
            [
                'Parameter #1 $ of callable callable(string, int): string expects string, int given.',
                13,
                'default.latte',
            ],
            [
                'Parameter #2 $ of callable callable(string, int): string expects int, string given.',
                13,
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
                'Register it in phpstan.neon: parameters > latte > filters. See https://github.com/efabrica-team/phpstan-latte/docs/configuration.md#filters',
            ],
        ]);
    }

    public function testLinks(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/LinksPresenter.php'], [
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
                'Method ' . LinksPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                13,
                'default.latte',
            ],
            [
                'Method ' . LinksPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                14,
                'default.latte',
            ],
            [
                'Method ' . LinksPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                16,
                'default.latte',
            ],
            [
                'Method ' . LinksPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                17,
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
                'Method ' . LinksPresenter::class . '::actionEdit() invoked with 3 parameters, 1-2 required.',
                56,
                'default.latte',
            ],
            [
                'Method ' . LinksPresenter::class . '::actionEdit() invoked with 3 parameters, 1-2 required.',
                57,
                'default.latte',
            ],
            [
                'Method ' . LinksPresenter::class . '::actionEdit() invoked with 3 parameters, 1-2 required.',
                59,
                'default.latte',
            ],
            [
                'Method ' . LinksPresenter::class . '::actionEdit() invoked with 3 parameters, 1-2 required.',
                60,
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
        ]);
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
                'Dumped type: 9',
                1,
                '@recursionB.latte',
            ],
            [
                'Dumped type: 8',
                1,
                '@recursionB.latte',
            ],
            [
                'Dumped type: 7',
                1,
                '@recursionB.latte',
            ],
            [
                'Dumped type: 6',
                1,
                '@recursionB.latte',
            ],
        ]);
    }

    public function testResolve(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ResolvePresenter.php'], [
            [
                'Variable $nonExistingVariable might not be defined.',
                3,
                'empty.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                3,
                'recursion.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                3,
                'throwSometimes.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                3,
                'setFile.changed.latte',
            ],
            [
                'Latte template was not set for ' . ResolvePresenter::class . '::sendTemplateDefault',
                89,
                'ResolvePresenter.php',
            ],
            [
                'Cannot automatically resolve latte template from expression.',
                95,
                'ResolvePresenter.php',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                3,
                'sendTemplate.latte',
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
        ]);
    }

    public function testStartupView(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/StartupViewPresenter.php'], [
            [
                'Variable $nonExistingVariable might not be defined.', // action default
                4,
                'default.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.', // action parent
                4,
                'parent.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.', // action startup
                4,
                'startup.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.', // action default(startup)
                4,
                'startup.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.', // action parent(startup)
                4,
                'startup.latte',
            ],
        ]);
    }
}
