<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Helper;

use Efabrica\PHPStanLatte\Template\Form\FormField;
use PHPStan\Type\TypeCombinator;

final class FormFieldHelper
{
    /**
     * @param array<FormField> ...$formFieldArrays
     * @return array<FormField>
     */
    public static function union(array ...$formFieldArrays): array
    {
        $union = [];
        foreach ($formFieldArrays as $formFieldArray) {
            foreach ($formFieldArray as $formField) {
                $formFieldName = $formField->getName();
                if (isset($union[$formFieldName])) {
                    $union[$formFieldName] = new FormField(
                        $formFieldName,
                        TypeCombinator::union($union[$formFieldName]->getType(), $formField->getType())
                    );
                } else {
                    $union[$formFieldName] = $formField;
                }
            }
        }
        return array_values($union);
    }

    /**
     * @param array<FormField> ...$formFieldArrays
     * @return array<FormField>
     */
    public static function merge(array ...$formFieldArrays): array
    {
        $merge = [];
        foreach ($formFieldArrays as $formFieldArray) {
            foreach ($formFieldArray as $formField) {
                $formFieldName = $formField->getName();
                $merge[$formFieldName] = $formField;
            }
        }
        return array_values($merge);
    }
}
