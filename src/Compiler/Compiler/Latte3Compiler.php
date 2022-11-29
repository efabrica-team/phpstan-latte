<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Compiler;

use Latte\Compiler\TemplateGenerator;
use Latte\Engine;
use Latte\Essential\RawPhpExtension;
use Latte\Extension;
use Nette\Bridges\ApplicationLatte\UIExtension;
use Nette\Bridges\FormsLatte\FormsExtension;

final class Latte3Compiler extends AbstractCompiler
{
    /**
     * @param Extension[] $extensions
     */
    public function __construct(
        ?Engine $engine = null,
        bool $strictMode = false,
        array $extensions = []
    ) {
        parent::__construct($engine, $strictMode);
        $this->installExtensions($extensions);
    }

    protected function createDefaultEngine(): Engine
    {
        $engine = new Engine();
        if (class_exists(RawPhpExtension::class)) {
            $engine->addExtension(new RawPhpExtension());
        }
        if (class_exists(UIExtension::class)) {
            $engine->addExtension(new UIExtension(null));
        }
        if (class_exists(FormsExtension::class)) {
            $engine->addExtension(new FormsExtension());
        }
        return $engine;
    }

    public function compile(string $templateContent, ?string $actualClass): string
    {
        $templateNode = $this->engine->parse($templateContent);
        $this->engine->applyPasses($templateNode);
        $className = $this->generateClassName();
        $templateGenerator = new TemplateGenerator();
        $phpContent = $templateGenerator->generate(
            $templateNode,
            $className,
            $this->generateClassComment($className),
            $this->strictMode
        );
        $phpContent = $this->fixLines($phpContent);
        $phpContent = $this->addTypes($phpContent, $className, $actualClass);
        return $phpContent;
    }

    public function getFilters(): array
    {
        $filters = [];
        foreach ($this->engine->getExtensions() as $extension) {
            /** @var array<string, array{string, string}|string> $filters */
            $filters = array_merge($filters, $extension->getFilters());
        }
        return $filters;
    }

    /**
     * @param Extension[] $extensions
     */
    private function installExtensions(array $extensions): void
    {
        foreach ($extensions as $extension) {
            $this->engine->addExtension($extension);
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
