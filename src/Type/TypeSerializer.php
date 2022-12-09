<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Type;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use Throwable;

class TypeSerializer
{
    private TypeStringResolver $typeStringResolver;

    public function __construct(TypeStringResolver $typeStringResolver)
    {
        $this->typeStringResolver = $typeStringResolver;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(Type $type): array
    {
        $typeAsArray = [
            'precise' => $type->describe(VerbosityLevel::precise()),
            'basic' => $type->describe(VerbosityLevel::typeOnly()),
        ];
        try {
            $typeAsArray['serialized'] = @serialize($type);
        } catch (Throwable $e) {
            // ignore
        }
        return $typeAsArray;
    }

    /**
     * @param array<string, string> $typeAsArray
     */
    public function fromArray(array $typeAsArray): Type
    {
        if (isset($typeAsArray['serialised'])) {
            $type = unserialize($typeAsArray['serialised']);
            if (!$type instanceof Type) {
                throw new ShouldNotHappenException();
            }
            return $type;
        }
        try {
            $type = $this->typeStringResolver->resolve($typeAsArray['precise']);
            if ($type instanceof ErrorType) {
                $type = $this->typeStringResolver->resolve($typeAsArray['basic']);
            }
            return $type;
        } catch (Throwable $e) {
            return $this->typeStringResolver->resolve($typeAsArray['basic']);
        }
    }
}
