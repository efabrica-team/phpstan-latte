<?php

use Latte\Engine;

$engine = new Engine();
$engine->addFilter('closure', function () {
});
$engine->addFilter('closureWithIntParam', function (int $param) {
});
$engine->addFilter('closureWithStringParam', function (string $param) {
});
return $engine;
