<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTest;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\TestPresenter\FooPresenter;
use Latte\Engine;

final class LatteTemplatesRuleForPresenterTest extends LatteTemplatesRuleTest
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../extension.neon',
            Engine::VERSION_ID < 30000 ? __DIR__ . '/../../../../latte2.neon' : __DIR__ . '/../../../../latte3.neon',
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/Fixtures/config.neon',
        ];
    }

    public function test(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/TestPresenter/FooPresenter.php'], [
            [
                'Variable $items might not be defined.',
                5,
            ],
            [
                'Undefined latte filter "nonExistingFilter".',
                8,
                'Register it in phpstan.neon: parameters > latte > filters. See https://github.com/efabrica-team/phpstan-latte#filters',
            ],
            [
                'Method ' . FooPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                16,
            ],
            [
                'Method ' . FooPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                17,
            ],
            [
                'Method ' . FooPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                19,
            ],
            [
                'Method ' . FooPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                20,
            ],
            [
                'Method ' . FooPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                22,
            ],
            [
                'Method ' . FooPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                23,
            ],
            [
                'Method ' . FooPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                25,
            ],
            [
                'Method ' . FooPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                26,
            ],
            [
                'Method ' . FooPresenter::class . '::actionEdit() invoked with 0 parameters, 1-2 required.',
                29,
            ],
            [
                'Method ' . FooPresenter::class . '::actionEdit() invoked with 0 parameters, 1-2 required.',
                30,
            ],
            [
                'Parameter #1 $id of method ' . FooPresenter::class . '::actionEdit() expects string, array<string, string> given.',
                35,
            ],
            [
                'Parameter #1 $id of method ' . FooPresenter::class . '::actionEdit() expects string, array<string, string> given.',
                36,
            ],
            [
                'Parameter #1 $id of method ' . FooPresenter::class . '::actionEdit() expects string, array<string, int|string> given.',
                47,
            ],
            [
                'Parameter #1 $id of method ' . FooPresenter::class . '::actionEdit() expects string, array<string, int|string> given.',
                48,
            ],

            [
                'Component with name "nonExistingControl" probably doesn\'t exist.',
                66,
            ],
//            [
//                'Component with name "someControl-nonexisting" probably doesn\'t exist.',
//                28,
//            ],
        ]);
    }
}
