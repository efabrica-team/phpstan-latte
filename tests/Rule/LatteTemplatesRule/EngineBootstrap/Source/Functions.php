<?php

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\EngineBootstrap;

final class Functions
{
    public function objectFunction(string $input): string
    {
        return $input;
    }

    public static function methodFunction(string $input): string
    {
        return $input;
    }
}
