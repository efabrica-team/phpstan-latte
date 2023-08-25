<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithModule;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTest;

final class LatteTemplatesRuleForPresenterTest extends LatteTemplatesRuleTest
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

    public function testStandalone(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/Modules/Bar/Presenters/BarBarPresenter.php',
            __DIR__ . '/Fixtures/Modules/Bar/Presenters/BarFooPresenter.php',
            __DIR__ . '/Fixtures/Modules/Foo/Presenters/FooBarPresenter.php',
            __DIR__ . '/Fixtures/Modules/Foo/Presenters/FooFooPresenter.php',
        ], [
            [
                'Undefined variable: $title',
                3,
                'add.latte',
            ],
            [
                'Undefined variable: $title',
                3,
                'add.latte',
            ],
            [
                'Undefined variable: $title',
                3,
                'default.latte',
            ],
            [
                'Undefined variable: $title',
                3,
                'add.latte',
            ],
            [
                'Undefined variable: $title',
                3,
                'default.latte',
            ],
            [
                'Undefined variable: $title',
                3,
                'add.latte',
            ],
        ]);
    }
}
