<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

final class Template
{
    private string $path;

    /** @var Variable[] */
    private array $variables;

    /**
     * @param Variable[] $variables
     */
    public function __construct(string $path, array $variables)
    {
        $this->path = $path;
        $this->variables = $variables;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return Variable[]
     */
    public function getVariables(): array
    {
        return $this->variables;
    }
}
