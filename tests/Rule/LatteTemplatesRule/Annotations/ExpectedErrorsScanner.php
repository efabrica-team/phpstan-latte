<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations;

use RuntimeException;
use function explode;
use function file_get_contents;
use function pathinfo;
use function preg_match_all;
use function substr;
use function trim;
use const PATHINFO_BASENAME;
use const PREG_OFFSET_CAPTURE;

final class ExpectedErrorsScanner
{
    /**
     * @return list<array{error: string, line: int, tip: ?string}>
     */
    public function scanErrorsFromFile(string $file, $pattern): array
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
}
