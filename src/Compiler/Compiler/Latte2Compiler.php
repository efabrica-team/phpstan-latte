<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Compiler;

use Latte\Compiler;
use Latte\Parser;
use Latte\Runtime\Defaults;

final class Latte2Compiler implements CompilerInterface
{
    private bool $strictMode;

    /** @var string[] */
    private array $macros;

    private Parser $parser;

    private Compiler $compiler;

    /**
     * @param string[] $macros
     */
    public function __construct(
        bool $strictMode,
        array $macros,
        Parser $parser,
        Compiler $compiler
    ) {
        $this->strictMode = $strictMode;
        $this->macros = $macros;
        $this->parser = $parser;
        $this->compiler = $compiler;
    }

    public function compile(string $templateContent): string
    {
        $latteTokens = $this->parser->parse($templateContent);
        $this->installMacros($this->compiler);
        $phpContent = $this->compiler->compile($latteTokens, 'PHPStanLatteTemplate', null, $this->strictMode);
        return $this->fixLines($phpContent);
    }

    public function getDefaultFilters(): array
    {
        $defaults = new Defaults();
        /** @var array<string, string|array{string, string}> $defaultFilters */
        $defaultFilters = array_change_key_case($defaults->getFilters());
        return $defaultFilters;
    }

    private function installMacros(Compiler $compiler): void
    {
        foreach ($this->macros as $macro) {
            [$class, $method] = explode('::', $macro, 2);
            $callable = [$class, $method];
            if (is_callable($callable)) {
                call_user_func($callable, $compiler);
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
