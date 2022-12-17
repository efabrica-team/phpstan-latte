<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source;

use Nette\Application\UI\Form;

trait PresenterTrait
{
    /** @var int[] */
    private array $propertyIntList = [];

    public function actionTrait(): void
    {
        $this->template->string = 'foo';
        $localStrings = ['foo', 'bar', 'baz'];
        $this->template->localStrings = $localStrings;
        $this->template->propertyIntList = $this->propertyIntList;
    }

    protected function createComponentFormFromTrait(): Form
    {
        $form = new Form();
        return $form;
    }
}
