<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\Blocks;

use Nette\Application\UI\Control;
use stdClass;

final class SomeControl extends Control
{
    public function renderDefine(): void
    {
        $this->template->knownObject = new stdClass();
        $this->template->knownString = 'a';
        $this->template->knownFloat = 1.23;
        $this->template->knownInteger = 123;
        $this->template->paramString = 'some string';

        $this->template->setFile(__DIR__ . '/define.latte');
        $this->template->render();
    }
}
