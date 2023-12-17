<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

use Efabrica\PHPStanLatte\Type\TemplateTypeHelper;
use PHPStan\Type\TypeCombinator;

final class ItemCombinator
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
                    $union[$itemName] = $item->withType(TypeCombinator::union($union[$itemName]->getType(), $item->getType()));
                } else {
                    $union[$itemName] = $item;
                }
            }
        }
        return array_values($union);
    }

    /**
     * @param NameTypeItem[] $items
     * @return NameTypeItem[]
     */
    public static function resolveTemplateTypes(array $items, string $declaringClass, ?string $currentClass): array
    {
        $resolvedItems = [];
        foreach ($items as $item) {
            $resolvedItems[] = $item->withType(TemplateTypeHelper::resolveTemplateType($item->getType(), $declaringClass, $currentClass));
        }

        return $resolvedItems;
    }
}
