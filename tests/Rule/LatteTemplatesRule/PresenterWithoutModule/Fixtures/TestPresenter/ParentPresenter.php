<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\TestPresenter;

use Nette\Application\UI\Presenter;

class ParentPresenter extends Presenter
{
    protected function startup()
    {
        parent::startup();
    }

    public function actionDefault(): void
    {
        $this->template->variableFromParentCalledViaParent = 'barbaz';
    }

    protected function baz(): void
    {
        $this->template->variableFromParent = 'baz';
    }
}
