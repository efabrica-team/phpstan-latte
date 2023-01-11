<?php

namespace Efabrica\PHPStanLatte\LatteContext\Resolver;

use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Filter;
use Efabrica\PHPStanLatte\Template\Form\Form;
use Efabrica\PHPStanLatte\Template\Variable;

interface LatteContextResolverInterface
{
    /**
     * @return Variable[]
     */
    public function getVariables(): array;

    /**
     * @return Component[]
     */
    public function getComponents(): array;

    /**
     * @return Form[]
     */
    public function getForms(): array;

    /**
     * @return Filter[]
     */
    public function getFilters(): array;
}
