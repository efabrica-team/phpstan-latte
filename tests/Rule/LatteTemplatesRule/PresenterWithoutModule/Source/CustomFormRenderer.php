<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source;

use Nette\Forms\Control;
use Nette\Forms\Rendering\DefaultFormRenderer;

final class CustomFormRenderer extends DefaultFormRenderer
{
    public function someCustomMethod(Control $control): string
    {
        return is_string($control->getValue()) ? $control->getValue() : '';
    }
}
