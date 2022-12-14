<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

use Exception;
use Nette\Application\UI\Presenter;

final class ResolvePresenter extends Presenter
{
    public function actionEmpty(): void
    {
    }

    public function actionRecursion()
    {
        $this->recursion(5);
    }

    public function recursion(int $counter): void
    {
        $this->template->variableFromRecursionMethod = 'bar';
        if ($counter > 0) {
            $this->recursion($counter - 1);
        }
    }

    public function actionRedirect(): void
    {
        $this->redirect('default');
    }

    public function actionIndirectRedirect(): void
    {
        $this->actionRedirect();
    }

    /**
     * @return never
     */
    public function actionThrow(): void
    {
        throw new Exception('Not renderable');
    }

    public function actionIndirectThrow(): void
    {
        $this->actionThrow();
    }

    public function actionCalledThrow(): void
    {
        $this->throwError();
    }

    /**
     * @return never
     */
    private function throwError(): void
    {
        throw new Exception('Not renderable');
    }

    public function actionThrowSometimes(bool $param): void
    {
        if ($param) {
            throw new Exception('Not renderable');
        }
    }

    public function actionSetFile(): void
    {
        $this->template->setFile(__DIR__ . '/templates/Resolve/setFile.changed.latte');
    }
}
