<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Compiler;

use InvalidArgumentException;
use Latte\Compiler\TemplateGenerator;
use Latte\Engine;
use Latte\Essential\RawPhpExtension;
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
        ?string $engineBootstrap = null
    ) {
        $this->strictMode = $strictMode;
        if ($engineBootstrap !== null) {
            $engine = require $engineBootstrap;
            if (!$engine instanceof Engine) {
                throw new InvalidArgumentException('engineBootstrap must return Engine');
            }
        } else {
            $engine = new Engine();
            if (class_exists(RawPhpExtension::class)) {
                $extensions[] = new RawPhpExtension();
            }
            if (class_exists(UIExtension::class)) {
                $extensions[] = new UIExtension(null);
            }
            if (class_exists(FormsExtension::class)) {
                $extensions[] = new FormsExtension();
            }

            $this->installExtensions($engine, $extensions);
        }
        $this->engine = $engine;
    }

    public function compile(string $templateContent): string
    {
        $templateNode = $this->engine->parse($templateContent);
        $this->engine->applyPasses($templateNode);
        $templateGenerator = new TemplateGenerator();
        $phpContent = $templateGenerator->generate($templateNode, 'PHPStanLatteTemplate', null, $this->strictMode);
        return $this->fixLines($phpContent);
    }

    public function getDefaultFilters(): array
    {
        $defaultFilters = [];
        foreach ($this->engine->getExtensions() as $extension) {
            /** @var array<string, array{string, string}|string> $defaultFilters */
            $defaultFilters = array_merge($defaultFilters, $extension->getFilters());
        }
        return $defaultFilters;
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

    private function fixLines(string $phpContent): string
    {
        // fix lines after $component->render()
        $pattern = '/\$ʟ_tmp = \$this->global->uiControl->getComponent(.*?)\$ʟ_tmp->render\((.*?)\) (?<line>(.*?)\/\*(.*?)line (?<number>\d+)(.*?)\*\/);/s';
        $phpContent = preg_replace($pattern, '${3}' . "\n\t\t" . '$ʟ_tmp = $this->global->uiControl->getComponent${1}$ʟ_tmp->render(${2});', $phpContent) ?: '';

        // fix lines at the end of lines
        $pattern = '/(.*?) (?<line>\/\*(.*?)line (?<number>\d+)(.*?)\*\/)/';
        return preg_replace($pattern, '${2}${1}', $phpContent) ?: '';
    }
}
