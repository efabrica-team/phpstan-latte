<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\Hierarchy;

/**
 * @template T
 * @extends GrandParentControl<T>
 */
abstract class ParentControl extends GrandParentControl
{
    public function render(): void
    {
        $this->template->parent = 'parent';
        parent::render();
    }

    public function renderParent(): void
    {
        $this->template->parent = 'parent';
        parent::renderParent();
    }

    public function setTemplateData()
    {
        $this->template->parentData = 'parentData';
        parent::setTemplateData();
    }
}
