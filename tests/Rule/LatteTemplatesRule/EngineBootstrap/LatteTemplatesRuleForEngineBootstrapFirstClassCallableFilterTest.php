<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\EngineBootstrap;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTestCase;

/**
 * @requires PHP > 8.1
 */
final class LatteTemplatesRuleForEngineBootstrapFirstClassCallableFilterTest extends LatteTemplatesRuleTestCase
{
    protected static function additionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/../../../config.neon',
            __DIR__ . '/config-firstClassCallableFilter.neon',
        ];
    }

    public function testFilters(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/FirstClassCallableFilterPresenter.php'], [
        ]);
    }
}
