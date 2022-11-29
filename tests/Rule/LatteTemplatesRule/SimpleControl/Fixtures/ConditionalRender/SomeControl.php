<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\MultipleRenderMethods;

use Nette\Application\UI\Control;

final class SomeControl extends Control
{
    public function render(bool $param): void
    {
        $this->template->a = 'a';
        $this->template->b = 'b';

        if ($param) {
            $this->template->render(__DIR__ . '/default1.latte');
        } else {
            $this->template->render(__DIR__ . '/default2.latte');
        }
    }

    public function renderVar(bool $param): void
    {
        $this->template->a = 'a';
        $this->template->b = 'b';

        if ($param) {
            $file = __DIR__ . '/var1.latte';
        } else {
            $file = __DIR__ . '/var2.latte';
        }

        $this->template->render($file);
    }

    public function renderTemplateFileNotFound(bool $param): void
    {
        if ($param) {
            $file = __DIR__ . '/invalid_file1.latte';
        } else {
            $file = __DIR__ . '/invalid_file2.latte';
        }

        $this->template->render($file);
    }
}
