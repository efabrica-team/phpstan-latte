<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

final class LineMapper
{
    /** @var array<int, int> */
    private array $lines = [];

    public function add(int $original, int $latte): void
    {
        $this->lines[$original] = $latte;
        ksort($this->lines);
    }

    public function get(int $original): int
    {
        $foundLatteLine = 1;
        foreach ($this->lines as $originalLine => $latteLine) {
            if ($original < $originalLine) {
                break;
            }
            $foundLatteLine = $latteLine;
        }

        return $foundLatteLine;
    }

    public function reset(): void
    {
        $this->lines = [];
    }
}
