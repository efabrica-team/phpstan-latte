<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

use Efabrica\PHPStanLatte\Template\Variable;

interface VariablesNodeVisitorInterface
{
    /**
     * @param Variable[] $variables
     */
    public function setVariables(array $variables): void;
}
