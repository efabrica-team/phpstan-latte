<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithModule\Fixtures\Modules\Bar\Presenters;

use Nette\Application\UI\Presenter;

final class BarFooPresenter extends Presenter
{
    public function renderDefault(): void
    {
        $this->template->title = 'Bar foo default';
    }
}
