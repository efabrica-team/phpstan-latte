<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext;

use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class LatteContextHelper
{
    /**
     * @return Variable[]
     */
    public static function variablesFromType(Type $type): array
    {
        if ($type instanceof ObjectType) {
            $type = $type->toArray();
        }

        $variables = [];
        if ($type instanceof ConstantArrayType) {
            $keyTypes = $type->getKeyTypes();
            $valueTypes = $type->getValueTypes();
            foreach ($keyTypes as $k => $arrayKeyType) {
                if (!$arrayKeyType instanceof ConstantStringType) { // only string keys
                    continue;
                }
                $variableName = $arrayKeyType->getValue();
                $variables[$variableName] = new Variable($variableName, $valueTypes[$k]);
            }
        }
        return $variables;
    }

    /**
     * @return Variable[]
     */
    public static function variablesFromTemplateType(string $class): array
    {
        $classType = (new ObjectType($class))->toArray();
        return self::variablesFromType($classType);
    }
}
