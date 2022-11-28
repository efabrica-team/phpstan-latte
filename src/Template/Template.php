<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

use JsonSerializable;
use ReturnTypeWillChange;

final class Template implements JsonSerializable
{
    private string $path;

    /** @var ?class-string */
    private ?string $actualClass;

    /** @var Variable[] */
    private array $variables;

    /** @var Component[] */
    private array $components;

    /**
     * @param ?class-string $actualClass
     * @param Variable[] $variables
     * @param Component[] $components
     */
    public function __construct(string $path, ?string $actualClass, array $variables, array $components)
    {
        $this->path = $path;
        $this->actualClass = $actualClass;
        $this->variables = $variables;
        $this->components = $components;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return ?class-string
     */
    public function getActualClass(): ?string
    {
        return $this->actualClass;
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

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
          'path' => $this->path,
          'variables' => $this->variables,
          'components' => $this->components,
        ];
    }
}
