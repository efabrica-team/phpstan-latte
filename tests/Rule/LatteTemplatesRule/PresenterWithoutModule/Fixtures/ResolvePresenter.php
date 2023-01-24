<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

use Exception;
use Latte\Engine;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;

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

    public function actionExit(): void
    {
        exit();
    }

    public function actionExitSometimes(bool $param): void
    {
        if ($param) {
            exit();
        }
    }

    public function actionDie(): void
    {
        die();
    }

    public function actionDieSometimes(bool $param): void
    {
        if ($param) {
            die();
        }
    }

    public function actionSetFile(): void
    {
        $this->template->setFile(__DIR__ . '/templates/Resolve/setFile.changed.latte');
    }

    public function actionSendTemplate(): void
    {
        $this->template->send = 'send';
        $this->template->setFile(__DIR__ . '/templates/Resolve/sendTemplate.latte');
        $this->sendTemplate();
    }

    public function actionSendTemplateDefault(): void
    {
        $this->template->send = 'send';
        $this->sendTemplate();
    }

    public function actionSendTemplateUnresolvable(): void
    {
        $this->template->send = 'send';
        $this->sendTemplate(new DefaultTemplate(new Engine()));
    }

    public function dynamicClassName()
    {
        $class = uniqid();
        new $class();
        $class = ParentPresenter::class;
        new $class();
    }
}
