<?php

namespace Efabrica\PHPStanLatte\LatteContext\Resolver\Nette;

use Efabrica\PHPStanLatte\LatteContext\Resolver\ClassLatteContextResolver;
use Efabrica\PHPStanLatte\Template\Variable;

class NetteApplicationUIControlLatteContextResolver extends ClassLatteContextResolver
{
    public function getVariables(): array
    {
        return [
            new Variable('presenter', $this->getClassType()),
            new Variable('control', $this->getClassType()),
        ];
    }
}
