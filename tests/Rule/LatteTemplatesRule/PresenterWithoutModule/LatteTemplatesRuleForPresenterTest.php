<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTest;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\LinksPresenter;
use Latte\Engine;

final class LatteTemplatesRuleForPresenterTest extends LatteTemplatesRuleTest
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../extension.neon',
            Engine::VERSION_ID < 30000 ? __DIR__ . '/../../../../latte2.neon' : __DIR__ . '/../../../../latte3.neon',
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/config.neon',
        ];
    }

    public function testVariables(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/VariablesPresenter.php', __DIR__ . '/Fixtures/ParentPresenter.php'], [
            [
                'Variable $items might not be defined.',
                5,
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
                'Variable $fromRenderDefault might not be defined.',
                4,
                'other.latte',
            ],
            [
                'Variable $nonExistingVariable might not be defined.',
                5,
                'other.latte',
            ],
        ]);
    }

    public function testComponents(): void
    {
        // TODO https://github.com/efabrica-team/phpstan-latte/issues/24
        // TODO https://github.com/efabrica-team/phpstan-latte/issues/39

        $this->analyse([__DIR__ . '/Fixtures/ComponentsPresenter.php', __DIR__ . '/Fixtures/ParentPresenter.php'], [
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
                'Component with name "onlyParentDefaultForm" probably doesn\'t exist.',
                7,
                'create.latte',
            ],
            [
                'Component with name "nonExistingControl" probably doesn\'t exist.',
                11,
                'create.latte',
            ],
        ]);
    }

    public function testFilters(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/FiltersPresenter.php', __DIR__ . '/Fixtures/ParentPresenter.php'], [
            [
                'Undefined latte filter "nonExistingFilter".',
                2,
                'default.latte',
                'Register it in phpstan.neon: parameters > latte > filters. See https://github.com/efabrica-team/phpstan-latte#filters',
            ],
        ]);
    }

    public function testLinks(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/LinksPresenter.php', __DIR__ . '/Fixtures/ParentPresenter.php'], [
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
                'Method ' . LinksPresenter::class . '::actionEdit() invoked with 0 parameters, 1-2 required.',
                20,
                'default.latte',
            ],
            [
                'Method ' . LinksPresenter::class . '::actionEdit() invoked with 0 parameters, 1-2 required.',
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

        ]);
    }
}
