<?php

namespace Efabrica\PHPStanLatte\LatteContext\Resolver\Nette;

use Efabrica\PHPStanLatte\LatteContext\Resolver\ClassLatteContextResolver;
use Efabrica\PHPStanLatte\Template\ItemCombinator;
use Efabrica\PHPStanLatte\Template\Variable;

class NetteApplicationUIPresenterLatteContextResolver extends ClassLatteContextResolver
{
    public function getVariables(): array
    {
        return ItemCombinator::merge(
            $this->latteContext->variableFinder()->find($this->getClassName(), 'startup', 'beforeRender'),
            [
                new Variable('presenter', $this->getClassType()),
                new Variable('control', $this->getClassType()),
            ]
        );
    }

    public function getComponents(): array
    {
        return $this->latteContext->componentFinder()->find($this->getClassName(), 'startup', 'beforeRender');
    }

    public function getForms(): array
    {
        return $this->latteContext->formFinder()->find($this->getClassName(), 'startup', 'beforeRender');
    }

    public function getFilters(): array
    {
        return $this->latteContext->filterFinder()->find($this->getClassName(), 'startup', 'beforeRender');
    }
}
