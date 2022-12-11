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

        $this->template->setFile(__DIR__ . '/define.latte');
        $this->template->render();
    }
}
