<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

use stdClass;

final class FiltersPresenter extends ParentPresenter
{
    private ?string $subtitle = null;

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
        $this->template->subtitle = $this->subtitle;
        $this->template->someObject = new stdClass();
    }
}
