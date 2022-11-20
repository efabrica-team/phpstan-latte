<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\TestPresenter\FooPresenter;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTest;
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
                14,
            ],
            [
                'Method ' . FooPresenter::class . '::actionCreate() invoked with 1 parameter, 0 required.',
                15,
            ],
            [
                'Component with name "nonExistingControl" probably doesn\'t exist.',
                17,
            ],
            [
                'Component with name "someControl-nonexisting" probably doesn\'t exist.',
                28,
            ],
        ]);
    }
}
