<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule;

use PHPStan\Analyser\Error;
use PHPStan\Rules\Rule;

abstract class CollectorResultTest extends LatteTemplatesRuleTest
{
    protected function getRule(): Rule
    {
        return $this->getContainer()->getByType(CollectorResultRule::class);
    }

    public function analyse(array $files, array $expectedErrors): void
    {
        $actualErrors = $this->gatherAnalyserErrors($files);
        $actualErrors = array_map(static function (Error $error): string {
            return $error->getMessage();
        }, $actualErrors);
        $this->assertSame(implode("\n", $expectedErrors) . "\n", implode("\n", $actualErrors) . "\n");
    }
}
