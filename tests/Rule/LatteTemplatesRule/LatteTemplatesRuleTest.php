<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule;

use Efabrica\PHPStanLatte\Collector\AbstractCollector;
use Efabrica\PHPStanLatte\Rule\LatteTemplatesRule;
use Nette\Utils\Finder;
use PHPStan\Analyser\Error;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use RuntimeException;

abstract class LatteTemplatesRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return $this->getContainer()->getByType(LatteTemplatesRule::class);
    }

    protected function getCollectors(): array
    {
        $container = $this->getContainer();
        $collectors = [];
        foreach ($container->findServiceNamesByType(AbstractCollector::class) as $serviceName) {
            $collectors[] = $container->getService($serviceName);
        }
        return $collectors;
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

    /**
     * @return list<array{error: string, line: int, tip: ?string}>
     */
    public function parseErrorsFormFile(string $file, $pattern): array
    {
        $fileData = file_get_contents($file);
        if ($fileData === false) {
            throw new RuntimeException('Error while reading data from ' . $file);
        }

        $fileDataLines = explode("\n", $fileData);

        $expectedErrors = [];
        $expectedErrorsBuffer = [];

        foreach ($fileDataLines as $line => $row) {
            $matches = [];
            $matched = preg_match_all($pattern, $row, $matches, PREG_OFFSET_CAPTURE);

            if ($matched === false) {
                throw new RuntimeException('Error while matching errors');
            } else {
                $lineHasContent = false;
            }
            if ($matched > 0) {
                $lineHasContent = trim(substr($row, 0, $matches[0][0][1])) !== ''; // not only whitespace before error

                foreach ($matches[1] as $matchedError) {
                    $errorParts = explode('###', trim($matchedError[0]));
                    $error = $errorParts[0];
                    $tip = $errorParts[1] ?? null;

                    $expectedErrorsBuffer[] = [
                        'error' => trim($error),
                        'tip' => $tip !== null ? trim($tip) : null,
                        'file' => pathinfo($file, PATHINFO_BASENAME),
                    ];
                }
            }

            if ($matched === 0 || $lineHasContent) {
                foreach ($expectedErrorsBuffer as $expectedError) {
                    $expectedError['line'] = $line + 1;
                    $expectedErrors[] = $expectedError;
                }
                $expectedErrorsBuffer = [];
            }
        }
        return $expectedErrors;
    }

    /**
     * @return list<array{error: string, line: int, tip: ?string}>
     */
    public function parseErrorsFromLatte(string $path): array
    {
        $expectedErrors = [];
        foreach (Finder::findFiles('*.latte')->from($path) as $file) {
            $expectedErrors = array_merge($expectedErrors, $this->parseErrorsFormFile((string)$file, '#\{\* *ERROR:(.+) \*\}#'));
        }
        return $expectedErrors;
    }

    /**
     * @return list<array{error: string, line: int, tip: ?string}>
     */
    public function parseErrorsFromPhp(string $path): array
    {
        $expectedErrors = [];
        if (is_file($path)) {
            $expectedErrors = $this->parseErrorsFormFile($path, '#// *ERROR:(.+)#');
        } else {
            foreach (Finder::findFiles('*.php')->from($path) as $file) {
                $expectedErrors = array_merge($expectedErrors, $this->parseErrorsFormFile((string)$file, '#// *ERROR:(.+)#'));
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
            $this->parseErrorsFromPhp($path),
            $this->parseErrorsFromLatte($path)
        );
        foreach ($analysedFiles as $file) {
            $expectedErrors = array_merge($expectedErrors, $this->parseErrorsFromPhp($file));
        }
        if (file_exists($path . '/_expectedErrors.php')) {
            $expectedErrors = array_merge($expectedErrors, require($path . '/_expectedErrors.php'));
        }
        $analysedFiles = array_merge($analysedFiles, $this->findAnalysedFiles($path));
        $this->analyse($analysedFiles, $expectedErrors, $namespace);
    }
}
