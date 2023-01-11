<?php

namespace Efabrica\PHPStanLatte\LatteContext\Resolver\Nette;

use Efabrica\PHPStanLatte\LatteContext\Resolver\ClassLatteContextResolver;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\Type\ObjectType;

class NetteApplicationUIControlLatteContextResolver extends ClassLatteContextResolver
{
    public function getVariables(): array
    {
        return [
            new Variable('presenter', new ObjectType('Nette\Application\UI\Presenter')),
            new Variable('control', $this->getClassType()),
        ];
    }
}
