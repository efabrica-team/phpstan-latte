<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Compiler;

use Latte\Compiler\TemplateGenerator;
use Latte\Engine;
use Latte\Extension;
use Nette\Bridges\ApplicationLatte\UIExtension;
use Nette\Bridges\FormsLatte\FormsExtension;

final class Latte3Compiler implements CompilerInterface
{
    private bool $strictMode;

    private Engine $engine;

    /**
     * @param Extension[] $extensions
     */
    public function __construct(
        bool $strictMode,
        array $extensions,
        Engine $engine
    ) {
        $this->strictMode = $strictMode;
        if (class_exists(UIExtension::class)) {
            $extensions[] = new UIExtension(null);
        }
        if (class_exists(FormsExtension::class)) {
            $extensions[] = new FormsExtension();
        }

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
