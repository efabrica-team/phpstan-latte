<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\VariableCollector;

use Efabrica\PHPStanLatte\Compiler\Compiler\Latte2Compiler;
use Efabrica\PHPStanLatte\Compiler\Compiler\Latte3Compiler;
use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\VariableCollector\DynamicFilterVariables;
use Efabrica\PHPStanLatte\VariableCollector\VariableCollectorInterface;
use Nette\Localization\Translator;
use Nette\Utils\Strings;
use PHPStan\PhpDoc\TypeStringResolver;

final class DynamicFilterVariablesTest extends AbstractCollectorTest
{
    protected function createCollector(): VariableCollectorInterface
    {
        $filters = [
            'strlen' => 'strlen',
            'webalize' => [Strings::class, 'webalize'],
            'translate' => [Translator::class, 'translate'],
            'closure' => function () {
            },
        ];
        $typeStringResolver = $this->createMock(TypeStringResolver::class);
        if (LatteVersion::isLatte2()) {
            return new DynamicFilterVariables(new Latte2Compiler(null, false, $filters), $typeStringResolver);
        } else {
            return new DynamicFilterVariables(new Latte3Compiler(null, false, $filters), $typeStringResolver);
        }
    }

    protected function variablesCount(): int
    {
        return 2;
    }
}
