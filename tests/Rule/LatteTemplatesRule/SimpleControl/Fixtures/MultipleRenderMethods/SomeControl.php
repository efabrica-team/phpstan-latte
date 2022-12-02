<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\MultipleRenderMethods;

use Nette\Application\UI\Control;

final class SomeControl extends Control
{
    public function render(): void
    {
        $this->template->a = 'a';
        $this->template->b = 'b';

        $this->template->setFile(__DIR__ . '/default.latte');
        $this->template->render();
    }

    public function renderTest(): void
    {
        $this->template->c = 'a';
        $this->template->d = 'b';

        $this->template->setFile(__DIR__ . '/test.latte');
        $this->template->render();
    }

    public function renderWildcard(string $param): void
    {
        $this->template->a = 'a';
        $this->template->c = 'c';

        $this->template->setFile(__DIR__ . '/param_' . $param . '.latte');
        $this->template->render();
    }

    public function renderTemplateFileNotFound(): void
    {
        $this->template->setFile(__DIR__ . '/invalid_file.latte');
        $this->template->render();
    }
}
