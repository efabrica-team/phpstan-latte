<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\VariableCollector;

use Efabrica\PHPStanLatte\VariableCollector\DefaultTemplateVariables;
use Efabrica\PHPStanLatte\VariableCollector\VariableCollectorInterface;

final class DefaultTemplateVariablesTest extends AbstractCollectorTest
{
    protected function createCollector(): VariableCollectorInterface
    {
        return new DefaultTemplateVariables();
    }

    protected function variablesCount(): int
    {
        return 8;
    }
}
