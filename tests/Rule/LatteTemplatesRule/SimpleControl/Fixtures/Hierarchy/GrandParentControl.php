<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\Hierarchy;

use Nette\Application\UI\Control;

abstract class GrandParentControl extends Control
{
    public function render(): void
    {
        $this->template->grandParent = 'grandparent';
        $this->setTemplateData();
        $this->template->render(__DIR__ . '/default.latte');
    }

    public function renderParent(): void
    {
        $this->template->grandParent = 'grandparent';
        $this->setTemplateData();
        $this->template->render(__DIR__ . '/parent.latte');
    }

    public function renderGrandParent(): void
    {
        $this->template->grandParent = 'grandparent';
        $this->setTemplateData();
        $this->template->render(__DIR__ . '/grandParent.latte');
    }

    public function setTemplateData()
    {
        $this->template->grandParentData = 'grandparentData';
    }
}
