<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\PhpDoc;

final class LattePhpDoc
{
    private bool $isIgnored;

    public function __construct(bool $isIgnored = false)
    {
        $this->isIgnored = $isIgnored;
    }

    public function isIgnored(): bool
    {
        return $this->isIgnored;
    }

    public function merge(self $inherit): self
    {
        return new self(
            $inherit->isIgnored() || $this->isIgnored
        );
    }
}
