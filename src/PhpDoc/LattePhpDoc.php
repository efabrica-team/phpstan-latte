<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\PhpDoc;

use PHPStan\ShouldNotHappenException;

final class LattePhpDoc
{
    private bool $isIgnored;

    /** @var array<string>|null  */
    private ?array $templatePaths = null;

    private ?self $parent = null;

    /**
     * @param array<string>|null $templatePaths
     */
    public function __construct(bool $isIgnored = false, ?array $templatePaths = null)
    {
        $this->isIgnored = $isIgnored;
        $this->templatePaths = $templatePaths;
    }

    public function isIgnored(): bool
    {
        return $this->isIgnored || ($this->parent !== null && $this->parent->isIgnored());
    }

    /**
     * @return array<string>
     */
    public function getTemplatePaths(): ?array
    {
        return $this->templatePaths;
    }

    public function setParent(self $parent): void
    {
        if ($this->parent !== null) {
            throw new ShouldNotHappenException('Cannot change LattePhpDoc parent');
        }
        $this->parent = $parent;
    }
}
