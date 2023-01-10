<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\Hierarchy;

final class SomeControl extends ParentControl
{
    public function render(): void
    {
        $this->template->some = 'some';
        parent::render();
    }

    public function setTemplateData()
    {
        $this->template->data = 'data';
        parent::setTemplateData();
    }
}
