<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\EngineBootstrap\Fixtures\Filters;

use Nette\Application\UI\Presenter;

final class SomePresenter extends Presenter
{
    public function actionDefault(): void
    {
        $this->template->title = 'title';
    }
}
