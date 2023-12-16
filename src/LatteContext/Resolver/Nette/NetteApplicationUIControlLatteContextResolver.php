<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Resolver\Nette;

use Efabrica\PHPStanLatte\LatteContext\Resolver\ClassLatteContextResolver;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;

final class NetteApplicationUIControlLatteContextResolver extends ClassLatteContextResolver
{
    public function getVariables(): array
    {
        return [
            new Variable('presenter', new ObjectType('Nette\Application\UI\Presenter')),
            new Variable('control', $this->getClassType()),
            new Variable('flashes', new ArrayType(new MixedType(), new ObjectType('stdClass'))),
        ];
    }
}
