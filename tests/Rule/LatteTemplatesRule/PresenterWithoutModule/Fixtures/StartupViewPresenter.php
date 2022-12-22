<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

final class StartupViewPresenter extends ParentPresenter
{
    protected function startup()
    {
        parent::startup();
        if ($this->isAjax()) {
            $this->setView('startup');
        }
    }

    public function actionDefault(): void
    {
        $this->template->fromDefault = 'default';
    }

    public function renderDefault(): void
    {
        $this->template->fromRenderDefault = 'default';
    }

    public function renderStartup(): void
    {
        $this->template->fromRenderStartup = 'startup';
    }
}
