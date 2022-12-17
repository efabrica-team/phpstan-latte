<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source\PresenterTrait;
use Nette\Application\UI\Presenter;

final class TraitPresenter extends Presenter
{
    use PresenterTrait;
}
