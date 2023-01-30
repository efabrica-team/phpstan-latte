<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use Throwable;

final class TypeToPhpDoc
{
    private TypeStringResolver $typeStringResolver;

    public function __construct(TypeStringResolver $typeStringResolver)
    {
        $this->typeStringResolver = $typeStringResolver;
    }

    public function toPhpDocString(Type $type): string
    {
        if ($type instanceof StaticType) {
            $type = $type->getStaticObjectType();
        }

        // replace unresolved template types with their bounds (T of stdClass -> stdClass)
        $type = TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
            if ($type instanceof TemplateType) {
                return $traverse($type->getBound());
            }
            return $traverse($type);
        });

        $phpDoc = $type->describe(VerbosityLevel::precise());
        try {
            $resolveBack = $this->typeStringResolver->resolve($phpDoc);
            if ($resolveBack instanceof ErrorType) {
                $phpDoc = $type->describe(VerbosityLevel::typeOnly());
            }
        } catch (Throwable $e) {
            $phpDoc = $type->describe(VerbosityLevel::typeOnly());
        }
        return $phpDoc;
    }
}
