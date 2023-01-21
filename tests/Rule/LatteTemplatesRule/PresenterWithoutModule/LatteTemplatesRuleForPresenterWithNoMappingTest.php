<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTest;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\LinksPresenter;

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
        // Without mapping only handle links are created, no other errors should be found
        $this->analyse([__DIR__ . '/Fixtures/LinksPresenter.php'], [
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
        ]);
    }
}
