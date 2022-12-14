<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source;

use Nette\Application\UI\Form;

trait PresenterTrait
{
    protected function createComponentFormFromTrait(): Form
    {
        $form = new Form();
        return $form;
    }
}
