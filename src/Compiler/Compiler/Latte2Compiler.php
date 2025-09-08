<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Compiler;

use Latte\CompileException;
use Latte\Engine;
use Latte\Runtime\Defaults;
use Latte\Runtime\FilterExecutor;
use Nette\Bridges\ApplicationLatte\UIMacros;
use Nette\Bridges\FormsLatte\FormMacros;
use ReflectionClass;
use ReflectionException;
use function is_int;

final class Latte2Compiler extends AbstractCompiler
{
    /** @var string[] */
    private array $macros = [];

    /**
     * @param array<string, string|array{string, string}> $filters
     * @param string[] $macros
     */
    public function __construct(
        ?Engine $engine = null,
        bool $strictMode = false,
        array $filters = [],
        array $functions = [],
        array $macros = []
    ) {
        parent::__construct($engine, $strictMode, $filters, $functions);
        $this->macros = $macros;
        $this->installMacros($macros);
    }

    public function getCacheKey(): string
    {
        return md5(
            implode('', array_keys($this->getFilters())) .
            implode('', array_keys($this->getFunctions())) .
            implode('', $this->macros)
        );
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

    public function compile(string $templateContent, ?string $actualClass, string $context = ''): string
    {
        $parser = $this->engine->getParser();
        $compiler = $this->engine->getCompiler();
        try {
            $latteTokens = $parser->parse($templateContent);
            $className = $this->generateClassName();
            $phpContent = $compiler
                ->setFunctions(array_keys($this->getFunctions()))
                ->compile(
                    $latteTokens,
                    $className,
                    $this->generateClassComment($className, $context),
                    $this->strictMode
                );
        } catch (CompileException $e) {
            $line = isset($latteTokens)
                ? $compiler->getLine()
                : $parser->getLine();

            $e->setSource($templateContent, is_int($line) ? $line : null, '');
            throw $e;
        }
        $phpContent = $this->fixLines($phpContent);
        $phpContent = $this->addTypes($phpContent, $className, $actualClass);
        return $phpContent;
    }

    public function getFilters(): array
    {
        try {
            $engineFilters = $this->getEngineFiltersByReflection();
        } catch (ReflectionException $e) {
            $engineFilters = $this->getDefaultFilters();
        }
        return array_merge($engineFilters, array_change_key_case($this->filters));
    }

    public function getFunctions(): array
    {
        try {
            $engineFunctions = $this->getEngineFunctionsByReflection();
        } catch (ReflectionException $e) {
            $engineFunctions = $this->getDefaultFunctions();
        }
        return array_change_key_case(array_merge($engineFunctions, $this->functions));
    }

    /**
     * @return array<string, string|array{string, string}|array{object, string}|callable>
     * @throws ReflectionException
     */
    private function getEngineFiltersByReflection(): array
    {
        // we must use try to use reflection to get to filter signatures in Latte 2 :-(
        $engineFiltersPropertyReflection = (new ReflectionClass($this->engine))->getProperty('filters');
        $engineFiltersPropertyReflection->setAccessible(true);
        /** @var FilterExecutor $engineFiltersProperty */
        $engineFiltersProperty = $engineFiltersPropertyReflection->getValue($this->engine);

        $engineFiltersStaticPropertyReflection = (new ReflectionClass($engineFiltersProperty))->getProperty('_static');
        $engineFiltersStaticPropertyReflection->setAccessible(true);
        /** @var array<string, array{callable, ?bool}> */
        $engineFiltersStaticProperty = $engineFiltersStaticPropertyReflection->getValue($engineFiltersProperty);

        $engineFilters = [];
        foreach ($engineFiltersStaticProperty as $filterName => $filterData) {
            $engineFilters[strtolower($filterName)] = $filterData[0];
        }
        return $engineFilters;
    }

    /**
     * @return array<string, callable>
     * @throws ReflectionException
     */
    private function getEngineFunctionsByReflection(): array
    {
        // we must use try to use reflection to get to filter signatures in Latte 2 :-(
        $engineFunctionsPropertyReflection = (new ReflectionClass($this->engine))->getProperty('functions');
        $engineFunctionsPropertyReflection->setAccessible(true);
        /** @var array<string, callable> $engineFunctionsProperty */
        $engineFunctionsProperty = $engineFunctionsPropertyReflection->getValue($this->engine);

        $engineFunctions = [];
        foreach ($engineFunctionsProperty as $functionName => $callable) {
            $engineFunctions[strtolower($functionName)] = $callable;
        }
        return $engineFunctions;
    }

    /**
     * @return array<string, string|array{string, string}|callable>
     */
    private function getDefaultFilters(): array
    {
        /** @var array<string, callable> $defaultFilters */
        $defaultFilters = $this->getDefaultFunctions();
        return array_change_key_case($defaultFilters);
    }

    /**
     * @return array<string, callable>
     */
    private function getDefaultFunctions(): array
    {
        /** @var array<string, callable> */
        return (new Defaults())->getFunctions();
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
