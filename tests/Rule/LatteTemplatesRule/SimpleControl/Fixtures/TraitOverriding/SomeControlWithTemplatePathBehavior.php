<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\TraitOverriding;

final class SomeControlWithTemplatePathBehavior extends BaseControl
{
    use TemplatePathBehavior;

    public function render(): void
    {
        $this->template->a = 'c';
        $this->template->b = 'd';

        $this->template->setFile($this->getTemplatePath());
        $this->template->render();
    }
}
