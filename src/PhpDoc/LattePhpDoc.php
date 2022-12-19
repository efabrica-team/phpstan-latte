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

    private ?self $parentMethod = null;

    private ?self $parentClass = null;

    /**
     * @param array<string> $templatePaths
     * @param array<string, Type> $variables
     */
    public function __construct(bool $isIgnored = false, array $templatePaths = [], array $variables = [])
    {
        $this->isIgnored = $isIgnored;
        $this->templatePaths = $templatePaths;
        $this->variables = $variables;
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
     * @return array<string, Type>
     */
    public function getVariables(string $defaultName = null): array
    {
        $variables = [];
        foreach ($this->variables as $name => $type) {
            $name = $name !== '' ? $name : $defaultName;
            if ($name !== null) {
                $variables[$name] = $type;
            }
        }
        return $variables;
    }

    /**
     * @return array<string, Type>
     */
    public function getVariablesWithParents(string $defaultName = null): array
    {
        return array_merge(
            ($this->parentClass !== null ? $this->parentClass->getVariables($defaultName) : []),
            ($this->parentMethod !== null ? $this->parentMethod->getVariables($defaultName) : []),
            $this->getVariables($defaultName)
        );
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
