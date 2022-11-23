<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

final class FiltersPresenter extends ParentPresenter
{
    public function actionDefault(): void
    {
        $this->template->title = 'foo';
    }
}
