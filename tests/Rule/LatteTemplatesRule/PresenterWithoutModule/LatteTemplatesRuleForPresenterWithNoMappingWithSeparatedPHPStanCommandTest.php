<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule;

final class LatteTemplatesRuleForPresenterWithNoMappingWithSeparatedPHPStanCommandTest extends LatteTemplatesRuleForPresenterWithNoMappingTest
{
    protected static function additionalConfigFiles(): array
    {
        return array_merge(parent::additionalConfigFiles(), [
            __DIR__ . '/phpstanCommand.neon',
        ]);
    }
}
