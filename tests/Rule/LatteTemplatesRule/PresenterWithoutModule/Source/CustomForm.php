<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source;

use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextArea;
use Nette\Forms\Controls\TextInput;

/**
 * @method CustomFormRenderer getRenderer()
 * @method TextInput|TextArea addCustomText(string $name = 'custom_default', ?string $label = null, ?int $cols = null, ?int $maxLength = null)
 */
class CustomForm extends Form
{

}
