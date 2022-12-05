<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
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
