<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\EngineBootstrap;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTest;

final class LatteTemplatesRuleForEngineBootstrapTest extends LatteTemplatesRuleTest
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/../../../config.neon',
            __DIR__ . '/config.neon',
        ];
    }

    public function testFilters(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/FiltersPresenter.php'], [
            [
                'Trying to invoke mixed but it\'s not a callable.',
                2,
                'default.latte',
            ],
            [
                'Undefined latte filter "nonExistingFilter".',
                2,
                'default.latte',
                'Register it in phpstan.neon: parameters > latte > filters. See https://github.com/efabrica-team/phpstan-latte#filters',
            ],
            [
                'Closure invoked with 1 parameter, 0 required.',
                3,
                'default.latte',
            ],
            [
                'Parameter #1 $ of closure expects int, string given.',
                4,
                'default.latte',
            ],
        ]);
    }
}
