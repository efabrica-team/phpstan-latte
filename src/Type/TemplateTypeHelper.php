<?php

namespace Efabrica\PHPStanLatte\Type;

use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

class TemplateTypeHelper
{
    public static function resolveTemplateType(Type $type, string $declaringClass, ?string $currentClass): Type
    {
        $currentType = new ObjectType($currentClass ?? $declaringClass);
        $declaringType = $currentType->getAncestorWithClassName($declaringClass);
        if ($declaringType === null) {
            return $type;
        }
        $declaringClassReflection = $declaringType->getClassReflection();
        if ($declaringClassReflection === null) {
            return $type;
        }
        $typeMap = $declaringClassReflection->getActiveTemplateTypeMap();
        return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($typeMap): Type {
            if ($type instanceof TemplateType) {
                $newType = $typeMap->getType($type->getName());
                if ($newType === null) {
                    return $traverse($type);
                }
                if ($newType instanceof ErrorType) {
                    return $traverse($type->getBound());
                }
                return $newType;
            }
            return $traverse($type);
        });
    }
}
