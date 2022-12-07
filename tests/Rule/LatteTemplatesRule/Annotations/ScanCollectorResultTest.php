<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\CollectorResultTest;
use Nette\Utils\Finder;

abstract class ScanCollectorResultTest extends CollectorResultTest
{
    private $expectedErrorsScanner;

    public function setUp(): void
    {
        $this->expectedErrorsScanner = new ExpectedErrorsScanner();
    }

    /**
     * @return list<array{error: string, line: int, tip: ?string}>
     */
    public function scanCollectedFromPhp(string $path): array
    {
        $expectedCollectedErrors = [];
        if (is_file($path)) {
            $expectedCollectedErrors = $this->expectedErrorsScanner->scanErrorsFromFile($path, '#// *COLLECT:(.+)#');
        } else {
            foreach (Finder::findFiles('*.php')->from($path) as $file) {
                $expectedCollectedErrors = array_merge($expectedCollectedErrors, $this->expectedErrorsScanner->scanErrorsFromFile((string)$file, '#// *COLLECT:(.+)#'));
            }
        }
        $expectedCollected = [];
        foreach ($expectedCollectedErrors as $expected) {
            $expectedCollected[] = $expected['error'];
        }
        return $expectedCollected;
    }

    /**
     * @return array<string>
     */
    public function findAnalysedFiles(string $path): array
    {
        $analysedFiles = [];
        foreach (Finder::findFiles('*.php')->in($path) as $file) {
            $analysedFiles[] = (string)$file;
        }
        return $analysedFiles;
    }

    public function resolveFixture(string $path, string $namespace, array $analysedFiles = []): void
    {
        $expectedCollected = $this->scanCollectedFromPhp($path);
        foreach ($analysedFiles as $file) {
            $expectedCollected = array_merge($expectedCollected, $this->scanCollectedFromPhp($file));
        }
        $analysedFiles = array_merge($analysedFiles, $this->findAnalysedFiles($path));
        $this->analyse($analysedFiles, $expectedCollected, $namespace);
    }
}
