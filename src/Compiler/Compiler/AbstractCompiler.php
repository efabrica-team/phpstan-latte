<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Compiler;

use Latte\Engine;

abstract class AbstractCompiler implements CompilerInterface
{
    protected bool $strictMode;

    protected Engine $engine;

    public function __construct(?Engine $engine = null, bool $strictMode = false)
    {
        if ($engine === null) {
            $engine = $this->createDefaultEngine();
        }
        $this->engine = $engine;
        $this->strictMode = $strictMode;
    }

    abstract protected function createDefaultEngine(): Engine;
}
