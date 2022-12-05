<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\EngineBootstrap\Fixtures;

use Nette\Application\UI\Presenter;

final class FiltersPresenter extends Presenter
{
    public function actionDefault(): void
    {
        $this->template->title = 'title';
    }
}
