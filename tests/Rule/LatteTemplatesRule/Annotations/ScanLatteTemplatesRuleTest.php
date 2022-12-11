<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTest;
use Nette\Utils\Finder;

abstract class ScanLatteTemplatesRuleTest extends LatteTemplatesRuleTest
{
    private $expectedErrorsScanner;

    public function setUp(): void
    {
        $this->expectedErrorsScanner = new ExpectedErrorsScanner();
    }

    /**
     * @return list<array{error: string, line: int, tip: ?string}>
     */
    public function scanErrorsFromLatte(string $path): array
    {
        $expectedErrors = [];
        foreach (Finder::findFiles('*.latte')->from($path) as $file) {
            $expectedErrors = array_merge($expectedErrors, $this->expectedErrorsScanner->scanErrorsFromFile((string)$file, '#\{\* *ERROR:(.+) \*\}#'));
        }
        return $expectedErrors;
    }

    /**
     * @return list<array{error: string, line: int, tip: ?string}>
     */
    public function scanErrorsFromPhp(string $path): array
    {
        $expectedErrors = [];
        if (is_file($path)) {
            $expectedErrors = $this->expectedErrorsScanner->scanErrorsFromFile($path, '#// *ERROR:(.+)#');
        } else {
            foreach (Finder::findFiles('*.php')->from($path) as $file) {
                $expectedErrors = array_merge($expectedErrors, $this->expectedErrorsScanner->scanErrorsFromFile((string)$file, '#// *ERROR:(.+)#'));
            }
        }
        return $expectedErrors;
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

    public function analyseFixture(string $path, string $namespace, array $analysedFiles = []): void
    {
        $expectedErrors = array_merge(
            $this->scanErrorsFromPhp($path),
            $this->scanErrorsFromLatte($path)
        );
        foreach ($analysedFiles as $file) {
            $expectedErrors = array_merge($expectedErrors, $this->scanErrorsFromPhp($file));
        }
        if (file_exists($path . '/_expectedErrors.php')) {
            $expectedErrors = array_merge($expectedErrors, require($path . '/_expectedErrors.php'));
        }
        $analysedFiles = array_merge($analysedFiles, $this->findAnalysedFiles($path));
        $this->analyse($analysedFiles, $expectedErrors, $namespace);
    }
}
