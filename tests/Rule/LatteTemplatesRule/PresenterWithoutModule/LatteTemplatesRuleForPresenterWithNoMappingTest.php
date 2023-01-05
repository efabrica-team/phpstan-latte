<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTest;

final class LatteTemplatesRuleForPresenterWithNoMappingTest extends LatteTemplatesRuleTest
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/../../../config.neon',
            __DIR__ . '/config.neon',
        ];
    }

    public function testLinks(): void
    {
        // Without mapping no links are created, so no errors are found
        $this->analyse([__DIR__ . '/Fixtures/LinksPresenter.php'], []);
    }
}
