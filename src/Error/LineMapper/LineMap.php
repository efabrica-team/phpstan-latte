<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error\LineMapper;

final class LineMap
{
    /** @var array<int, int> */
    private array $lines = [];

    /**
     * @param array<int, int> $lines
     */
    public function __construct(array $lines = [])
    {
        $this->lines = $lines;
    }

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

    /**
     * @return array<int, int>
     */
    public function getLines(): array
    {
        return $this->lines;
    }
}
