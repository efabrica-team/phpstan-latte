<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\EngineBootstrap;

use Latte\Engine;

final class FirstClassCallableFilters
{
    public function objectFilter(string $input): string
    {
        return $input;
    }
}

$engine = new Engine();
$engine->addFilter('objectFilterFirstClassCallable', (new FirstClassCallableFilters())->objectFilter(...));
return $engine;
