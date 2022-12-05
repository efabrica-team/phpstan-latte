<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\Resolve;

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
}
