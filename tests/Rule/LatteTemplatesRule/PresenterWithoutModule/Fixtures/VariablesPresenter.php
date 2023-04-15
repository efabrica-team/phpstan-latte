<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

final class VariablesPresenter extends ParentPresenter
{
    /** @var array<string[]> */
    private array $stringLists = [];

    protected function startup()
    {
        parent::startup();
        $this->template->startup = 'startup';
    }

    public function actionDefault(): void
    {
        parent::actionDefault();
        $this->template->title = 'foo';
        $this->bar();
        $this->baz();
        $this->overwritten();
        $this->overwrittenThroughtParent();
        $this->calledParentOverwritten();
        $this->getTemplate()->viaGetTemplate = 'foobar';
        $this->template->stringLists = $this->stringLists;
        $localStrings = ['foo', 'bar', 'baz'];
        $this->template->add('localStrings', $localStrings);
        $name = 'dynamic';
        $this->template->add($name, $name);
        $this->template->obj = $this;

        $items = ['first item', 'second item', 'third item'];
        [$this->template->array1, $this->template->array2,] = $items;
        list($this->template->list1, $this->template->list2,) = $items;

        [$this->template->array1WithoutType, $this->template->array2WithoutType] = $this->itemsToArrayAssignWithoutTypes();
        list($this->template->list1WithType, $this->template->list2WithType) = $this->itemsToArrayAssignWithTypes();

        $variable = $this->stringLists[0];
        $this->template->variableFromMethodCallOnVariable = $variable::method();
        $this->template->someOtherVariableWithDefault = 'value from presenter';

        $this->template->nullOrUrl = null;
        if (mt_rand()) {
            $this->template->nullOrUrl = 'https://example.org';
        }
    }

    public function renderDefault(): void
    {
        $var = 'variable';
        $this->template->encapsedVariable = "encapsed $var";
        $this->template->setParameters([
            'fromRenderDefault' => "from $var",
        ]);
    }

    public function actionOther(string $param): void
    {
        $name = 'fromOtherAction';
        $this->template->setParameters([
            $name => 'from other action',
        ]);
        $this->template->unresolvedInclude = $param;
    }

    protected function bar(): void
    {
        $this->template->variableFromOtherMethod = 'bar';
    }

    protected function overwritten(): void
    {
        $this->template->overwritting = 'overwritting';
    }

    protected function parentOverwritten(): void
    {
        $this->template->parentOverwritting = 'overwritting';
    }

    protected function calledParentOverwritten(): void
    {
        parent::calledParentOverwritten();
        $this->template->calledParentOverwritting = 'overwritted';
    }

    protected function calledParentSecondOverwritten(): void
    {
        $this->template->calledParentSecondOverwritting = 'overwritted';
    }

    public function actionDirectRender(): void
    {
        $this->template->fromTemplate = 'a';
        $this->template->render(__DIR__ . '/templates/Variables/direct.latte', ['fromRender' => 'b']);
        $this->terminate();
    }

    public function actionDynamicInclude(): void
    {
        $this->template->dynamicIncludeVar = 'a';
        $this->template->includedTemplate = __DIR__ . '/templates/Variables/@includedDynamically.latte';
    }

    public function renderOnlyRender(): void
    {
        $this->template->fromOnlyRender = 'from only render';
    }

    public function actionDifferentRender()
    {
        $this->template->fromDifferentRenderAction = 'from different render';
        $this->setView('different');
    }

    public function actionDifferentRenders(bool $param)
    {
        $this->template->fromDifferentRendersAction = 'from different renders';
        $this->setView($param ? 'different' : 'different2');
    }

    public function actionDifferentRenderConditional(bool $param)
    {
        if ($param) {
            $this->setView('different');
        }
    }

    public function actionDifferentRenderIndirect()
    {
        $this->changeView();
    }

    public function changeView()
    {
        $this->setView('different');
    }

    public function renderDifferent(): void
    {
        $this->template->fromDifferentRender = 'from different render 1';
    }

    public function renderDifferent2(): void
    {
        $this->template->fromDifferentRender2 = 'from different render 2';
    }

    private function itemsToArrayAssignWithoutTypes(): array
    {
        return ['one', 'two'];
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function itemsToArrayAssignWithTypes(): array
    {
        return ['three', 4];
    }
}
