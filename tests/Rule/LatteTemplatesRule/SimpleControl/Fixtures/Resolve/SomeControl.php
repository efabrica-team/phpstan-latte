<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\Resolve;

use Exception;
use Nette\Application\UI\Control;

final class SomeControl extends Control
{
    public function renderConstVar(): void
    {
        $this->template->a = 'a';
        $this->template->b = 'b';
        $var = '/constVar.latte';
        $this->template->render(__DIR__ . $var);
    }

    public function renderExplicit(): void
    {
        $this->template->a = 'a';
        $this->template->b = 'b';
        $this->template->setFile(__DIR__ . '/error.latte');
        $this->template->render(__DIR__ . '/explicit.latte');
    }

    public function renderDefaultVars(): void
    {
        $this->template->setFile(__DIR__ . '/defaultVars.latte');
        $this->template->render(null, ['a' => 'a', 'b' => 'b']);
    }

    public function renderExplicitVars(): void
    {
        $this->template->setFile(__DIR__ . '/error.latte');
        $this->template->render(__DIR__ . '/explicitVars.latte', ['a' => 'a', 'b' => 'b']);
    }

    public function renderDefaultObject(): void
    {
        $this->template->setFile(__DIR__ . '/defaultObject.latte');
        $this->template->render(null, new SomeControlTemplateType());
    }

    public function renderExplicitObject(): void
    {
        $this->template->setFile(__DIR__ . '/error.latte');
        $this->template->render(__DIR__ . '/explicitObject.latte', new SomeControlTemplateType());
    }

    /**
     * @param array{"a.b": string, 'b*c'?: int} $param
     */
    public function renderComplexType(array $param): void
    {
        $this->template->render(__DIR__ . '/complexType.latte', ['a' => $param, 'b' => $param]);
    }

    public function renderError(): void
    {
        $this->error();
    }

    public function renderIndirectError(): void
    {
        $this->renderError();
    }

    /**
     * @return never
     */
    public function renderThrow(): void
    {
        throw new Exception('Not renderable');
    }

    public function renderIndirectThrow(): void
    {
        $this->renderThrow();
    }

    public function renderCalledThrow(): void
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

    public function renderThrowSometimes(bool $param): void
    {
        if ($param) {
            throw new Exception('Not renderable');
        }
        $this->template->render(__DIR__ . '/throwSometimes.latte');
    }

    public function renderOutput(): void
    {
        echo 'output';
    }

    public function renderIndirectOutput(): void
    {
        $this->renderOutput();
    }

    public function renderPrint(): void
    {
        print 'output';
    }

    public function renderPrintR(): void
    {
        print_r($this);
    }

    public function renderVarDump(): void
    {
        var_dump($this);
    }
}
