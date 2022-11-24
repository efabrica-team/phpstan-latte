<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

use JsonSerializable;

final class Template implements JsonSerializable
{
    private string $path;

    /** @var Variable[] */
    private array $variables;

    /** @var Component[] */
    private array $components;

    /**
     * @param Variable[] $variables
     * @param Component[] $components
     */
    public function __construct(string $path, array $variables, array $components)
    {
        $this->path = $path;
        $this->variables = $variables;
        $this->components = $components;
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

    /**
     * @return Component[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
          'path' => $this->path,
          'variables' => $this->variables,
          'components' => $this->components,
        ];
    }
}
