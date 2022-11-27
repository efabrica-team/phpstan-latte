<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

use Latte\Engine;

final class LatteVersion
{
    public static function isLatte2(): bool
    {
        return Engine::VERSION_ID < 30000;
    }

    public static function isLatte3(): bool
    {
        return Engine::VERSION_ID >= 30000;
    }
}
