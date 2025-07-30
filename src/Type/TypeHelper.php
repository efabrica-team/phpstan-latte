<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Type;

use InvalidArgumentException;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

final class TypeHelper
{
    public static function resolveType(Type $type): Type
    {
        $type = TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
            if ($type instanceof StaticType) {
                return $traverse($type->getStaticObjectType());
            }
            return $traverse($type);
        });

        return $type;
    }

    public static function resolveTypeBounds(Type $type): Type
    {
        $type = self::resolveType($type);

        // replace unresolved template types with their bounds (T of stdClass -> stdClass)
        $type = TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
            if ($type instanceof TemplateType) {
                return $traverse($type->getBound());
            }
            return $traverse($type);
        });

        return $type;
    }

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

    public static function serializeType(Type $type): string
    {
        $type = TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
            if ($type instanceof StaticType) {
                return $traverse($type->getStaticObjectType());
            }
            if ($type instanceof ErrorType) {
                throw new InvalidArgumentException('Cannot serialize ErrorType');
            }
            if ($type instanceof TemplateType) {
                throw new InvalidArgumentException('Cannot serialize TemplateType');
            }
            return $traverse($type);
        });

        return (new Printer())->print($type->toPhpDocNode());
    }
}
