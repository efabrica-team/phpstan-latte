<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\TypeResolver;

use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

final class TemplateTypeResolver
{
    public function resolve(Type $type): bool
    {
        if ($type instanceof ObjectType) {
            return $type->isInstanceOf('Nette\Application\UI\Template')->yes();
        } elseif ($type instanceof UnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($this->resolve($unionType)) {
                    return true;
                }
            }
        }
        return false;
    }
}
