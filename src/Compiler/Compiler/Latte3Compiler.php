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
     * @param array<string, string|array{string, string}> $filters
     * @param Extension[] $extensions
     */
    public function __construct(
        ?Engine $engine = null,
        bool $strictMode = false,
        array $filters = [],
        array $extensions = []
    ) {
        parent::__construct($engine, $strictMode, $filters);
        $this->installExtensions($extensions);
    }

    public function getFilters(): array
    {
        return array_merge($this->engine->getFilters(), $this->filters);
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

    public function compile(string $templateContent, ?string $actualClass, string $context = ''): string
    {
        $templateNode = $this->engine->parse($templateContent);
        $this->engine->applyPasses($templateNode);
        $className = $this->generateClassName();
        $templateGenerator = new TemplateGenerator();
        $phpContent = $templateGenerator->generate(
            $templateNode,
            $className,
            $this->generateClassComment($className, $context),
            $this->strictMode
        );
        $phpContent = $this->fixLines($phpContent);
        $phpContent = $this->addTypes($phpContent, $className, $actualClass);
        return $phpContent;
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
