<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Helper;

use Efabrica\PHPStanLatte\Compiler\LatteVersion;

final class FilterHelper
{
    public static function createFilterVariableName(string $filterName): string
    {
        return '__filter__' . (LatteVersion::isLatte2() ? strtolower($filterName) : $filterName);
    }

    /**
     * @param string|array{string, string}|array{object, string}|callable $filter
     */
    public static function isCallableString($filter): bool
    {
        return is_string($filter) && (str_starts_with($filter, 'Closure(') || str_starts_with($filter, '\Closure(') || str_starts_with($filter, 'callable('));
    }
}
