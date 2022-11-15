<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule;

use Efabrica\PHPStanLatte\Rule\LatteTemplatesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

final class LatteTemplatesRuleTest extends RuleTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../extension.neon',
            __DIR__ . '/../../../rules.neon',

            // TODO add config - mappings, filters etc.
        ];
    }

    protected function getRule(): Rule
    {
        return $this->getContainer()->getByType(LatteTemplatesRule::class);
    }

    public function testPresenter(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/TestPresenter/FooPresenter.php'], [
            [
                'Variable $items might not be defined.',
                5,
            ],
            [
                'Undefined latte filter "nonExistingFilter".',
                7,
                'Register it in phpstan.neon: parameters > latte > filters. See https://github.com/efabrica-team/phpstan-latte#filters',
            ],

            // TODO line 11 - extra argument in link

            // TODO line 13 - nonExistingControl
        ]);
    }
}
