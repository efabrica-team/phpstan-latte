<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Helper;

use Efabrica\PHPStanLatte\Template\Component;
use PHPStan\Type\TypeCombinator;

final class ComponentsHelper
{
    /**
     * @param array<Component> ...$componentArrays
     * @return array<Component>
     */
    public static function union(array ...$componentArrays): array
    {
        $union = [];
        foreach ($componentArrays as $componentArray) {
            foreach ($componentArray as $component) {
                $componentName = $component->getName();
                if (isset($union[$componentName])) {
                    $union[$componentName] = new Component(
                        $componentName,
                        TypeCombinator::union($union[$componentName]->getType(), $component->getType())
                    );
                } else {
                    $union[$componentName] = $component;
                }
            }
        }
        return array_values($union);
    }

    /**
     * @param array<Component> ...$componentArrays
     * @return array<Component>
     */
    public static function merge(array ...$componentArrays): array
    {
        $merge = [];
        foreach ($componentArrays as $componentArray) {
            foreach ($componentArray as $component) {
                $componentName = $component->getName();
                $merge[$componentName] = $component;
            }
        }
        return array_values($merge);
    }
}
