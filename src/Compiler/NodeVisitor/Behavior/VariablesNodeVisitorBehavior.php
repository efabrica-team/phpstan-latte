<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

use Efabrica\PHPStanLatte\Template\Variable;

trait VariablesNodeVisitorBehavior
{
    /** @var array<string, Variable> */
    private array $variables = [];

    /**
     * @param Variable[] $variables
     */
    public function setVariables(array $variables): void
    {
        foreach ($variables as $variable) {
            $this->variables[$variable->getName()] = $variable;
        }
    }
}
