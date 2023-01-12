<?php

namespace Efabrica\PHPStanLatte\Template;

use PHPStan\Type\TypeCombinator;

class ItemCombinator
{
    /**
     * @template T of NameItem
     * @param array<T> ...$itemArrays
     * @return array<T>
     */
    public static function merge(array ...$itemArrays): array
    {
        $merge = [];
        foreach ($itemArrays as $itemArray) {
            foreach ($itemArray as $item) {
                $itemName = $item->getName();
                $merge[$itemName] = $item;
            }
        }
        return array_values($merge);
    }

    /**
     * @template T of NameTypeItem
     * @param array<T> ...$itemArrays
     * @return array<T>
     */
    public static function union(array ...$itemArrays): array
    {
        $union = [];
        foreach ($itemArrays as $itemArray) {
            foreach ($itemArray as $item) {
                $itemName = $item->getName();
                if (isset($union[$itemName])) {
                    $class = get_class($item);
                    $union[$itemName] = new $class(
                        $itemName,
                        TypeCombinator::union($union[$itemName]->getType(), $item->getType())
                    );
                } else {
                    $union[$itemName] = $item;
                }
            }
        }
        return array_values($union);
    }
}
