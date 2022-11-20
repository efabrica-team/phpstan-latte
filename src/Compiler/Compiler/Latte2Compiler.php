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
        return $this->compiler->compile($latteTokens, 'PHPStanLatteTemplate', null, $this->strictMode);
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

    public function getDefaultFilters(): array
    {
        $defaults = new Defaults();
        /** @var array<string, string|array{string, string}> $defaultFilters */
        $defaultFilters = array_change_key_case($defaults->getFilters());
        return $defaultFilters;
    }
}
