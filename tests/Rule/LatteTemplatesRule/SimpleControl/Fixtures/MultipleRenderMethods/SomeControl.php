<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\MultipleRenderMethods;

use Nette\Application\UI\Control;

// COLLECT: NODE NetteApplicationUIControl {"className":"SomeControl"}
final class SomeControl extends Control
{
    public function render(): void
    {
        $this->template->a = 'a';
        $this->template->b = 'b';

        $this->template->setFile(__DIR__ . '/default.latte');
        // COLLECT: TEMPLATE default.latte SomeControl::render ["presenter","control","a","b"] []
        $this->template->render();
    }

    public function renderTest(): void
    {
        $this->template->c = 'a';
        $this->template->d = 'b';

        $this->template->setFile(__DIR__ . '/test.latte');
        // COLLECT: TEMPLATE test.latte SomeControl::renderTest ["presenter","control","c","d"] []
        $this->template->render();
    }

    public function renderWildcard(string $param): void
    {
        $this->template->a = 'a';
        $this->template->c = 'c';

        $this->template->setFile(__DIR__ . '/param_' . $param . '.latte');
        // COLLECT: TEMPLATE param_a.latte SomeControl::renderWildcard ["presenter","control","a","c"] []
        // COLLECT: TEMPLATE param_b.latte SomeControl::renderWildcard ["presenter","control","a","c"] []
        $this->template->render();
    }

    public function renderTemplateFileNotFound(): void
    {
        $this->template->setFile(__DIR__ . '/invalid_file.latte');
        // COLLECT: TEMPLATE invalid_file.latte SomeControl::renderTemplateFileNotFound ["presenter","control"] []
        $this->template->render();
    }
}
