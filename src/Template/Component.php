<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

final class Component
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
