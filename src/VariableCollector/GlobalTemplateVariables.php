<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\VariableCollector;

use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\PhpDoc\TypeStringResolver;

final class GlobalTemplateVariables implements VariableCollectorInterface
{
    private array $globalVariables;

    private TypeStringResolver $typeStringResolver;

    /**
     * @param array<string, string> $globalVariables
     */
    public function __construct(array $globalVariables, TypeStringResolver $typeStringResolver)
    {
        $this->globalVariables = $globalVariables;
        $this->typeStringResolver = $typeStringResolver;
    }

    /**
     * @return Variable[]
     */
    public function collect(): array
    {
        $variables = [];
        foreach ($this->globalVariables as $variable => $type) {
            $variables[] = new Variable($variable, $this->typeStringResolver->resolve($type));
        }
        return $variables;
    }
}
