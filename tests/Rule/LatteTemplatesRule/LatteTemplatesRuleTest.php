<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule;

use Efabrica\PHPStanLatte\Collector\ResolvedNodeCollector;
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

    protected function getCollectors(): array
    {
        return [
            $this->getContainer()->getByType(ResolvedNodeCollector::class),
        ];
    }

    public function analyse(array $files, array $expectedErrors, string $namespace = null): void
    {
        $actualErrors = $this->gatherAnalyserErrors($files);
        $strictlyTypedSprintf = static function (int $line, string $message, string $file, ?string $tip) use ($namespace): string {
            $message = $file . ':' . sprintf('%02d: %s', $line, $message);
            if ($tip !== null) {
                $message .= ' ### ' . $tip;
            }
            if ($namespace) {
                $message = str_replace($namespace . '\\', '', $message);
            }
            return $message;
        };
        $expectedErrors = array_map(static function (array $error) use ($strictlyTypedSprintf): string {
            return $strictlyTypedSprintf($error['line'] ?? $error[1], $error['error'] ?? $error[0], $error['file'] ?? $error[2], $error['tip'] ?? $error[3] ?? null);
        }, $expectedErrors);
        $actualErrors = array_map(static function (Error $error) use ($strictlyTypedSprintf): string {
            $line = $error->getLine();
            if ($line === null) {
                $line = -1;
            }
            return $strictlyTypedSprintf($line, $error->getMessage(), pathinfo($error->getFile(), PATHINFO_BASENAME), $error->getTip());
        }, $actualErrors);
        sort($actualErrors);
        sort($expectedErrors);
        $this->assertSame(implode("\n", $expectedErrors) . "\n", implode("\n", $actualErrors) . "\n");
    }
}
