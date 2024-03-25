<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\EngineBootstrap;

use Latte\Engine;

require_once __DIR__ . '/Source/Filters.php';
require_once __DIR__ . '/Source/Functions.php';

$engine = new Engine();
$engine->addFilter('closure', function () {
});
$engine->addFilter('closureWithIntParam', function (int $param) {
});
$engine->addFilter('closureWithStringParam', function (string $param) {
});
$engine->addFilter('objectFilter', [new Filters(), 'objectFilter']);
$engine->addFilter('methodStringFilter', Filters::class . '::methodFilter');
$engine->addFunction('closurefunction', function () {
});
$engine->addFunction('closurefunctionwithintparam', function (int $param) {
});
$engine->addFunction('closurefunctionwithstringparam', function (string $param) {
});
$engine->addFunction('objectfunction', [new Functions(), 'objectFunction']);
$engine->addFunction('methodstringfunction', Functions::class . '::methodFunction');
return $engine;
