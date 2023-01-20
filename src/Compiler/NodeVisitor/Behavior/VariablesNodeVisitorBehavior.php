<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

use Efabrica\PHPStanLatte\Template\Variable;

trait VariablesNodeVisitorBehavior
{
    /** @var Variable[] */
    private array $variables = [];

    /**
     * @param Variable[] $variables
     */
    public function setVariables(array $variables): void
    {
        $this->variables = $variables;
    }
}
