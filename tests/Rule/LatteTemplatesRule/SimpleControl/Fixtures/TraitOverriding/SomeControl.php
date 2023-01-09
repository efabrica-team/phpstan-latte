<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\TraitOverriding;

final class SomeControl extends BaseControl
{
    public function render(): void
    {
        $this->template->a = 'a';
        $this->template->b = 'b';

        $this->template->setFile($this->getTemplatePath());
        $this->template->render();
    }
}
