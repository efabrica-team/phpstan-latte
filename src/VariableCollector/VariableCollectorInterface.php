<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\VariableCollector;

use Efabrica\PHPStanLatte\Template\Variable;

interface VariableCollectorInterface
{
    /**
     * @return Variable[]
     */
    public function collect(): array;
}
