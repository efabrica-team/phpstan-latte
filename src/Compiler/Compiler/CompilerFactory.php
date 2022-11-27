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

    /** @var string[] */
    protected array $macros;

    /** @var Extension[]  */
    protected array $extensions;

    /**
     * @param string[] $macros
     * @param Extension[] $extensions
     */
    public function __construct(
        ?string $engineBootstrap = null,
        bool $strictMode = false,
        array $macros = [],
        array $extensions = []
    ) {
        $this->engineBootstrap = $engineBootstrap;
        $this->strictMode = $strictMode;
        $this->macros = $macros;
        $this->extensions = $extensions;
    }

    public function create(): CompilerInterface
    {
        $engine = null;
        if ($this->engineBootstrap !== null) {
            $engine = require $this->engineBootstrap;
            if (!$engine instanceof Engine) {
                throw new InvalidArgumentException('engineBootstrap must return Latte\Engine');
            }
        }

        if (LatteVersion::isLatte2()) {
            if (count($this->extensions) > 0) {
                throw new InvalidArgumentException('You cannot use configuration option latte > extensions with Latte 2');
            }
            return new Latte2Compiler($engine, $this->strictMode, $this->macros);
        } else {
            if (count($this->extensions) > 0) {
                throw new InvalidArgumentException('You cannot use configuration option latte > macros with Latte 3');
            }
            return new Latte3Compiler($engine, $this->strictMode, $this->extensions);
        }
    }
}
