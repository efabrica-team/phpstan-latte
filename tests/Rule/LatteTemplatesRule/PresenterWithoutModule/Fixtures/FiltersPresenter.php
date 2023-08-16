<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

final class FiltersPresenter extends ParentPresenter
{
    protected function startup()
    {
        parent::startup();
        $this->template->addFilter('startupFilter', function (string $string): string {
            return uniqid($string);
        });
    }

    public function actionDefault(): void
    {
        $this->template->addFilter('actionDefaultFilter', function (string $string): string {
            return uniqid($string);
        });
        $this->template->title = 'foo';
        $this->template->someObject = new \stdClass();
    }
}
