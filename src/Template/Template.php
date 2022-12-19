<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedForm;
use JsonSerializable;
use ReturnTypeWillChange;

final class Template implements JsonSerializable
{
    private string $path;

    /** @var ?class-string */
    private ?string $actualClass;

    private ?string $actualAction;

    /** @var Variable[] */
    private array $variables;

    /** @var Component[] */
    private array $components;

    /** @var CollectedForm[] */
    private array $forms;

    /** @var Filter[] */
    private array $filters;

    private ?string $parentTemplatePath;

    /**
     * @param ?class-string $actualClass
     * @param Variable[] $variables
     * @param Component[] $components
     * @param CollectedForm[] $forms
     * @param Filter[] $filters
     */
    public function __construct(
        string $path,
        ?string $actualClass,
        ?string $actualAction,
        array $variables,
        array $components,
        array $forms,
        array $filters,
        ?string $parentTemplatePath = null
    ) {
        $this->path = $path;
        $this->actualClass = $actualClass;
        $this->actualAction = $actualAction;
        $this->variables = $variables;
        $this->components = $components;
        $this->forms = $forms;
        $this->filters = $filters;
        $this->parentTemplatePath = $parentTemplatePath;
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

    public function getActualAction(): ?string
    {
        return $this->actualAction;
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
     * @return CollectedForm[]
     */
    public function getForms(): array
    {
        return $this->forms;
    }

    /**
     * @return Filter[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getParentTemplatePath(): ?string
    {
        return $this->parentTemplatePath;
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'path' => $this->path,
            'actualClass' => $this->actualClass,
            'actualAction' => $this->actualAction,
            'variables' => $this->variables,
            'components' => $this->components,
            'filters' => $this->filters,
            'parentTemplatePath' => $this->parentTemplatePath,
        ];
    }
}
