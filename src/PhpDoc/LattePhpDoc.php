<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\PhpDoc;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;

final class LattePhpDoc
{
    private bool $isIgnored;

    /** @var array<string> */
    private array $templatePaths = [];

    /** @var array<string, Type> */
    private array $variables = [];

    /** @var array<string, Type> */
    private array $components = [];

    private ?self $parentMethod = null;

    private ?self $parentClass = null;

    /**
     * @param array<string> $templatePaths
     * @param array<string, Type> $variables
     * @param array<string, Type> $components
     */
    public function __construct(bool $isIgnored = false, array $templatePaths = [], array $variables = [], array $components = [])
    {
        $this->isIgnored = $isIgnored;
        $this->templatePaths = $templatePaths;
        $this->variables = $variables;
        $this->components = $components;
    }

    public function isIgnored(): bool
    {
        return $this->isIgnored ||
           ($this->parentMethod !== null && $this->parentMethod->isIgnored()) ||
           ($this->parentClass !== null && $this->parentClass->isIgnored());
    }

    /**
     * @return array<string>
     */
    public function getTemplatePaths(): array
    {
        return $this->templatePaths;
    }

    /**
     * @return array<string>
     */
    public function getTemplatePathsWithParents(): array
    {
        return array_merge(
            ($this->parentClass !== null ? $this->parentClass->getTemplatePaths() : []),
            ($this->parentMethod !== null ? $this->parentMethod->getTemplatePaths() : []),
            $this->templatePaths
        );
    }

    /**
     * @param array<?string> $defaultNames
     * @return array<string, Type>
     */
    public function getVariables(array $defaultNames = []): array
    {
        $variables = [];
        foreach ($this->variables as $name => $type) {
            if ($name !== '') {
                $variables[$name] = $type;
            } else {
                foreach (array_filter($defaultNames) as $defaultName) {
                    $variables[$defaultName] = $type;
                }
            }
        }
        return $variables;
    }

    /**
     * @param array<?string> $defaultNames
     * @return array<string, Type>
     */
    public function getVariablesWithParents(array $defaultNames = []): array
    {
        return array_merge(
            ($this->parentClass !== null ? $this->parentClass->getVariables($defaultNames) : []),
            ($this->parentMethod !== null ? $this->parentMethod->getVariables($defaultNames) : []),
            $this->getVariables($defaultNames)
        );
    }

    public function hasVariables(): bool
    {
        return count($this->variables) > 0;
    }

    /**
     * @param array<?string> $defaultNames
     * @return array<string, Type>
     */
    public function getComponents(array $defaultNames = []): array
    {
        $components = [];
        foreach ($this->components as $name => $type) {
            if ($name !== '') {
                $components[$name] = $type;
            } else {
                foreach (array_filter($defaultNames) as $defaultName) {
                    $components[$defaultName] = $type;
                }
            }
        }
        return $components;
    }

    /**
     * @param array<?string> $defaultNames
     * @return array<string, Type>
     */
    public function getComponentsWithParents(array $defaultNames = []): array
    {
        return array_merge(
            ($this->parentClass !== null ? $this->parentClass->getComponents($defaultNames) : []),
            ($this->parentMethod !== null ? $this->parentMethod->getComponents($defaultNames) : []),
            $this->getComponents($defaultNames)
        );
    }

    public function hasComponents(): bool
    {
        return count($this->components) > 0;
    }

    public function setParentMethod(self $parent): void
    {
        if ($this->parentMethod !== null) {
            throw new ShouldNotHappenException('Cannot change LattePhpDoc parentMethod');
        }
        $this->parentMethod = $parent;
        $this->parentClass = $parent->getParentClass();
    }

    public function getParentMethod(): self
    {
        if ($this->parentMethod === null) {
            throw new ShouldNotHappenException('Cannot get LattePhpDoc parentMethod');
        }
        return $this->parentMethod;
    }

    public function setParentClass(self $parent): void
    {
        if ($this->parentClass !== null) {
            throw new ShouldNotHappenException('Cannot change LattePhpDoc parentMethod');
        }
        $this->parentClass = $parent;
    }

    public function getParentClass(): self
    {
        if ($this->parentClass === null) {
            throw new ShouldNotHappenException('Cannot get LattePhpDoc parentClass');
        }
        return $this->parentClass;
    }
}
