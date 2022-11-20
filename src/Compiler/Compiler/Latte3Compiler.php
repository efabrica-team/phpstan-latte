<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Compiler;

use Latte\Compiler\TemplateGenerator;
use Latte\Engine;
use Latte\Extension;

final class Latte3Compiler implements CompilerInterface
{
    private bool $strictMode;

    private Engine $engine;

    /** @var Extension[] */
    private array $extensions;

    /**
     * @param Extension[] $extensions
     */
    public function __construct(
        bool $strictMode,
        Engine $engine,
        array $extensions
    ) {
        $this->strictMode = $strictMode;
        $this->installExtensions($engine, $extensions);
        $this->engine = $engine;
    }

    public function compile(string $templateContent): string
    {
        $templateNode = $this->engine->parse($templateContent);
        $this->engine->applyPasses($templateNode);
        $templateGenerator = new TemplateGenerator();
        return $templateGenerator->generate($templateNode, 'PHPStanLatteTemplate', null, $this->strictMode);
    }

    /**
     * @param Extension[] $extensions
     */
    private function installExtensions(Engine $engine, array $extensions): void
    {
        foreach ($extensions as $extension) {
            $engine->addExtension($extension);
        }
    }

    public function getDefaultFilters(): array
    {
        $defaultFilters = [];
        foreach ($this->engine->getExtensions() as $extension) {
            $defaultFilters = array_merge($defaultFilters, $extension->getFilters());
        }
        return $defaultFilters;
    }
}
