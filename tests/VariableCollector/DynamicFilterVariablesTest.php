<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\VariableCollector;

use Efabrica\PHPStanLatte\VariableCollector\DynamicFilterVariables;
use Efabrica\PHPStanLatte\VariableCollector\VariableCollectorInterface;
use Nette\Localization\Translator;
use Nette\Utils\Strings;

final class DynamicFilterVariablesTest extends AbstractCollectorTest
{
    protected function createCollector(): VariableCollectorInterface
    {
        return new DynamicFilterVariables([
            'strlen' => 'strlen',
            'webalize' => [Strings::class, 'webalize'],
            'translate' => [Translator::class, 'translate'],
        ]);
    }

    protected function variablesCount(): int
    {
        return 1;
    }
}
