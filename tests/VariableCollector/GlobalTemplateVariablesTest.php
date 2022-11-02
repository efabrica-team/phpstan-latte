<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\VariableCollector;

use Efabrica\PHPStanLatte\Template\Variable;
use Efabrica\PHPStanLatte\VariableCollector\GlobalTemplateVariables;
use Efabrica\PHPStanLatte\VariableCollector\VariableCollectorInterface;
use PHPStan\PhpDoc\TypeStringResolver;

final class GlobalTemplateVariablesTest extends AbstractCollectorTest
{
    protected function createCollector(): VariableCollectorInterface
    {
        return new GlobalTemplateVariables([
            'foo' => 'string',
            'bar' => 'int',
            'baz' => 'stdClass',
            'qwe' => Variable::class
        ], $this->createMock(TypeStringResolver::class));
    }

    protected function variablesCount(): int
    {
        return 4;
    }
}
