<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule;

use Efabrica\PHPStanLatte\Rule\LatteTemplatesRule;
use PHPStan\Analyser\Error;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

abstract class LatteTemplatesRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return $this->getContainer()->getByType(LatteTemplatesRule::class);
    }

    public function analyse(array $files, array $expectedErrors): void
    {
        $actualErrors = $this->gatherAnalyserErrors($files);
        $strictlyTypedSprintf = static function (int $line, string $message, string $file, ?string $tip): string {
            $message = $file . "\n" . sprintf('%02d: %s', $line, $message);
            if ($tip !== null) {
                $message .= "\n    💡 " . $tip;
            }
            return $message;
        };
        $expectedErrors = array_map(static function (array $error) use ($strictlyTypedSprintf): string {
            return $strictlyTypedSprintf($error[1], $error[0], $error[2], $error[3] ?? null);
        }, $expectedErrors);
        $actualErrors = array_map(static function (Error $error) use ($strictlyTypedSprintf): string {
            $line = $error->getLine();
            if ($line === null) {
                $line = -1;
            }
            return $strictlyTypedSprintf($line, $error->getMessage(), pathinfo($error->getFile(), PATHINFO_BASENAME), $error->getTip());
        }, $actualErrors);
        $this->assertSame(implode("\n", $expectedErrors) . "\n", implode("\n", $actualErrors) . "\n");
    }
}
