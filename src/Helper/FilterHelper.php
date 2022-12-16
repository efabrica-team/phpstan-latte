<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Helper;

final class FilterHelper
{
    public static function createFilterVariableName(string $filterName): string
    {
        return '__filter__' . $filterName;
    }
}
