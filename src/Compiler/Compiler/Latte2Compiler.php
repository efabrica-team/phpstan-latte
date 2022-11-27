<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Compiler;

use Latte\Engine;
use Latte\Runtime\Defaults;
use Nette\Bridges\ApplicationLatte\UIMacros;
use Nette\Bridges\FormsLatte\FormMacros;

final class Latte2Compiler extends AbstractCompiler
{
    /**
     * @param string[] $macros
     */
    public function __construct(
        ?Engine $engine = null,
        bool $strictMode = false,
        array $macros = []
    ) {
        parent::__construct($engine, $strictMode);
        $this->installMacros($macros);
    }

    protected function createDefaultEngine(): Engine
    {
        $engine = new Engine();
        if (class_exists(UIMacros::class)) {
            UIMacros::install($engine->getCompiler());
        }
        if (class_exists(FormMacros::class)) {
            FormMacros::install($engine->getCompiler());
        }
        return $engine;
    }

    public function compile(string $templateContent): string
    {
        $latteTokens = $this->engine->getParser()->parse($templateContent);
        $phpContent = $this->engine->getCompiler()->compile($latteTokens, 'PHPStanLatteTemplate', null, $this->strictMode);
        return $this->fixLines($phpContent);
    }

    public function getFilters(): array
    {
        $defaults = new Defaults();
        /** @var array<string, string|array{string, string}> $defaultFilters */
        $defaultFilters = array_change_key_case($defaults->getFilters());
        return $defaultFilters;
    }

    /**
     * @param string[] $macros
     */
    private function installMacros(array $macros): void
    {
        foreach ($macros as $macro) {
            [$class, $method] = explode('::', $macro, 2);
            $callable = [$class, $method];
            if (is_callable($callable)) {
                call_user_func($callable, $this->engine->getCompiler());
            }
        }
    }

    private function fixLines(string $phpContent): string
    {
        // fix lines at the end of lines
        $pattern = '/(.*?) (?<line>\/\*(.*?)line (?<number>\d+)(.*?)\*\/)/';
        return preg_replace($pattern, '${2}${1}', $phpContent) ?: '';
    }
}
