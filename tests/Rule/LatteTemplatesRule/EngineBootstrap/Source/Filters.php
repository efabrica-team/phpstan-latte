<?php

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\EngineBootstrap;

final class Filters
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
