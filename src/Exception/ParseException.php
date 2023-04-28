<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Exception;

use Exception;

final class ParseException extends Exception
{
    private int $sourceLine;

    private string $compileFilePath;

    public function __construct(string $message, int $sourceLine, string $compileFilePath)
    {
        parent::__construct($message);
        $this->sourceLine = $sourceLine;
        $this->compileFilePath = $compileFilePath;
    }

    public function getSourceLine(): int
    {
        return $this->sourceLine;
    }

    public function getCompileFilePath(): string
    {
        return $this->compileFilePath;
    }
}
