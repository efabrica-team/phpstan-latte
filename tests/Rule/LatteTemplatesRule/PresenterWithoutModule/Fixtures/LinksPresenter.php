<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

final class LinksPresenter extends ParentPresenter
{
    public function actionDefault(): void
    {
    }

    public function actionCreate(): void
    {
    }

    public function actionEdit(string $id, int $sorting = 100): void
    {
    }
}
