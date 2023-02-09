<?php

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\EngineBootstrap;

use Latte\Engine;

class Filters
{
    public function objectFilter(string $input): string
    {
        return $input;
    }

    public static function methodFilter(string $input): string
    {
        return $input;
    }
}

$engine = new Engine();
$engine->addFilter('closure', function () {
});
$engine->addFilter('closureWithIntParam', function (int $param) {
});
$engine->addFilter('closureWithStringParam', function (string $param) {
});
$engine->addFilter('objectFilter', [new Filters(), 'objectFilter']);
$engine->addFilter('methodStringFilter', Filters::class . '::methodFilter');
return $engine;
