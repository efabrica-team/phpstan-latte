<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Helper;

use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\Type\TypeCombinator;

final class VariablesHelper
{
    /**
     * @param array<Variable> ...$variableArrays
     * @return array<Variable>
     */
    public static function union(array ...$variableArrays): array
    {
        $union = [];
        foreach ($variableArrays as $variableArray) {
            foreach ($variableArray as $variable) {
                $variableName = $variable->getName();
                if (isset($union[$variableName])) {
                    $union[$variableName] = new Variable(
                        $variableName,
                        TypeCombinator::union($union[$variableName]->getType(), $variable->getType())
                    );
                } else {
                    $union[$variableName] = $variable;
                }
            }
        }
        return array_values($union);
    }

    /**
     * @param array<Variable> ...$variableArrays
     * @return array<Variable>
     */
    public static function merge(array ...$variableArrays): array
    {
        $merge = [];
        foreach ($variableArrays as $variableArray) {
            foreach ($variableArray as $variable) {
                $variableName = $variable->getName();
                $merge[$variableName] = $variable;
            }
        }
        return array_values($merge);
    }
}
