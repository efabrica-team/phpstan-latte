<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source;

use Nette\Application\UI\Form;
use Nette\ComponentModel\IContainer;
use Nette\Forms\Controls\TextArea;
use Nette\Forms\Controls\TextInput;

/**
 * @method CustomFormRenderer getRenderer()
 * @method TextInput|TextArea addCustomText(string $name = 'custom_default', ?string $label = null, ?int $cols = null, ?int $maxLength = null)
 */
final class CustomForm extends Form
{
    private string $someCustomParameter;

    public function __construct(string $someCustomParameter, ?IContainer $parent = null, ?string $name = null)
    {
        parent::__construct($parent, $name);
        $this->someCustomParameter = $someCustomParameter;
    }

    public function getSomeCustomParameter(): string
    {
        return $this->someCustomParameter;
    }
}
