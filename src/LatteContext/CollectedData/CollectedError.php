<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

final class CollectedError extends CollectedLatteContextObject
{
    private string $message;

    private string $file;

    private ?int $line;

    public function __construct(string $message, string $file, ?int $line = null)
    {
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    public static function build(Node $node, Scope $scope, string $message): self
    {
        return new self(
            $message,
            $scope->getFile(),
            $node->getStartLine()
        );
    }
}
