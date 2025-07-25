<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule;

use PHPStan\Analyser\Error;
use PHPStan\Rules\Rule;

abstract class CollectorResultTestCase extends LatteTemplatesRuleTestCase
{
    protected function getRule(): Rule
    {
        return $this->getContainer()->getByType(CollectorResultRule::class);
    }

    public function analyse(array $files, array $expectedErrors, string $namespace = null): void
    {
        $actualErrors = $this->gatherAnalyserErrors($files);
        $actualErrors = array_map(static function (Error $error) use ($namespace): string {
            $message = $error->getMessage();
            if ($namespace) {
                $message = str_replace($namespace . '\\', '', $message);
            }
            $message = str_replace(__DIR__, '', $message);
            return $message;
        }, $actualErrors);
        sort($actualErrors);
        sort($expectedErrors);
        $this->assertSame(implode("\n", $expectedErrors) . "\n", implode("\n", $actualErrors) . "\n");
    }
}
