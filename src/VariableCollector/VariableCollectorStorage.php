<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\VariableCollector;

use Efabrica\PHPStanLatte\Template\Variable;

final class VariableCollectorStorage
{
    /** @var VariableCollectorInterface[] */
    private array $variableCollectors;

    /**
     * @param VariableCollectorInterface[] $variableCollectors
     */
    public function __construct(array $variableCollectors)
    {
        $this->variableCollectors = $variableCollectors;
    }

    /**
     * @return Variable[]
     */
    public function collectVariables(): array
    {
        $variablesFromCollectors = [];
        foreach ($this->variableCollectors as $variableCollector) {
            $variablesFromCollectors[] = $variableCollector->collect();
        }
        return array_merge(...$variablesFromCollectors);
    }
}
