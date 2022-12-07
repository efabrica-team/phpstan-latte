<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule;

use Nette\Utils\Finder;
use PHPStan\Analyser\Error;
use PHPStan\Rules\Rule;

abstract class CollectorResultTest extends LatteTemplatesRuleTest
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
            return $message;
        }, $actualErrors);
        sort($actualErrors);
        sort($expectedErrors);
        $this->assertSame(implode("\n", $expectedErrors) . "\n", implode("\n", $actualErrors) . "\n");
    }

    /**
     * @return list<array{error: string, line: int, tip: ?string}>
     */
    public function parseCollectedFromPhp(string $path): array
    {
        $expectedCollectedErrors = [];
        if (is_file($path)) {
            $expectedCollectedErrors = $this->parseErrorsFormFile($path, '#// *COLLECT:(.+)#');
        } else {
            foreach (Finder::findFiles('*.php')->from($path) as $file) {
                $expectedCollectedErrors = array_merge($expectedCollectedErrors, $this->parseErrorsFormFile((string)$file, '#// *COLLECT:(.+)#'));
            }
        }
        $expectedCollected = [];
        foreach ($expectedCollectedErrors as $expected) {
            $expectedCollected[] = $expected['error'];
        }
        return $expectedCollected;
    }

    public function resolveFixture(string $path, string $namespace, array $analysedFiles = []): void
    {
        $expectedCollected = $this->parseCollectedFromPhp($path);
        foreach ($analysedFiles as $file) {
            $expectedCollected = array_merge($expectedCollected, $this->parseCollectedFromPhp($file));
        }
        $analysedFiles = array_merge($analysedFiles, $this->findAnalysedFiles($path));
        $this->analyse($analysedFiles, $expectedCollected, $namespace);
    }
}
