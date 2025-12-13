<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule;

use function array_merge;

final class LatteTemplatesRuleForPresenterWithNoMappingWithSeparatedPHPStanCommandTest extends LatteTemplatesRuleForPresenterWithNoMappingTest
{
    protected static function additionalConfigFiles(): array
    {
        return array_merge(parent::additionalConfigFiles(), [
            __DIR__ . '/phpstanCommand.neon',
        ]);
    }
}
