<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\Resolve;

use Exception;
use Nette\Application\UI\Presenter;

// COLLECT: NODE NetteApplicationUIPresenter {"className":"SomePresenter"}
// COLLECT: NODE NetteApplicationUIPresenterStandalone {"className":"SomePresenter"}
final class SomePresenter extends Presenter
{
    // COLLECT: TEMPLATE empty.latte SomePresenter::empty ["presenter","control"] []
    public function actionEmpty(): void
    {
    }

    public function actionRecursion()
    {
        $this->recursion(5);
    }

    // COLLECT: TEMPLATE recursion.latte SomePresenter::recursion ["presenter","control","variableFromRecursionMethod"] []
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

    // COLLECT: TEMPLATE throwSometimes.latte SomePresenter::throwSometimes ["presenter","control"] []
    public function actionThrowSometimes(bool $param): void
    {
        if ($param) {
            throw new Exception('Not renderable');
        }
    }
}
