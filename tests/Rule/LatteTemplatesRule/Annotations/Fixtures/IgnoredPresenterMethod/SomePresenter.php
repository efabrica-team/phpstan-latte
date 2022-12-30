<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations\Fixtures\IgnoredPresenterMethod;

use Nette\Application\UI\Presenter;

// COLLECT: NODE NetteApplicationUIPresenter {"className":"SomePresenter"}
// COLLECT: NODE NetteApplicationUIPresenterStandalone {"className":"SomePresenter"}
final class SomePresenter extends Presenter
{
    /**
     * @phpstan-latte-ignore
     */
    public function actionDefault(): void
    {
    }
}
