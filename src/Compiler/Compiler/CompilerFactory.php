<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Compiler;

use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use InvalidArgumentException;
use Latte\Engine;
use Latte\Extension;

final class CompilerFactory
{
    protected bool $strictMode;

    protected ?string $engineBootstrap;

    /** @var array<string, string|array{string, string}> */
    private array $filters;

    /** @var array<string, string|array{string, string}> */
    private array $functions;

    /** @var string[] */
    protected array $macros;

    /** @var Extension[]  */
    protected array $extensions;

    private ?CompilerInterface $compiler = null;

    /** @var array<string, Engine> */
    private static $engines = [];

    /**
     * @param array<string, string|array{string, string}> $filters
     * @param array<string, string|array{string, string}> $functions
     * @param string[] $macros
     * @param Extension[] $extensions
     */
    public function __construct(
        ?string $engineBootstrap = null,
        bool $strictMode = false,
        array $filters = [],
        array $functions = [],
        array $macros = [],
        array $extensions = []
    ) {
        $this->engineBootstrap = $engineBootstrap;
        $this->strictMode = $strictMode;
        $this->filters = $filters;
        $this->functions = $functions;
        $this->macros = $macros;
        $this->extensions = $extensions;
    }

    public function create(): CompilerInterface
    {
        if ($this->compiler !== null) {
            return $this->compiler;
        }

        $engine = null;
        if ($this->engineBootstrap !== null) {
            if (isset(self::$engines[$this->engineBootstrap])) {
                $engine = self::$engines[$this->engineBootstrap];
            } else {
                $engine = require $this->engineBootstrap;
                if (!$engine instanceof Engine) {
                    throw new InvalidArgumentException('Bootstrap file must return instance of Latte\Engine');
                }
                self::$engines[$this->engineBootstrap] = $engine;
            }
        }

        if (LatteVersion::isLatte2()) {
            if (count($this->extensions) > 0) {
                throw new InvalidArgumentException('You cannot use configuration option latte > extensions with Latte 2');
            }
            $this->compiler = new Latte2Compiler($engine, $this->strictMode, $this->filters, $this->functions, $this->macros);
        } else {
            if (count($this->macros) > 0) {
                throw new InvalidArgumentException('You cannot use configuration option latte > macros with Latte 3');
            }
            $this->compiler = new Latte3Compiler($engine, $this->strictMode, $this->filters, $this->functions, $this->extensions);
        }

        return $this->compiler;
    }
}
