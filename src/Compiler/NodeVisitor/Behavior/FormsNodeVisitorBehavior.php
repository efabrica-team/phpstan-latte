<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedForm;

trait FormsNodeVisitorBehavior
{
    /** @var array<string, CollectedForm> */
    private array $forms = [];

    /** @var array<string, string> */
    private array $formClassNames = [];

    private ?CollectedForm $actualForm = null;

    /**
     * @param CollectedForm[] $forms
     */
    public function setForms(array $forms): void
    {
        foreach ($forms as $form) {
            $formName = $form->getName();

            // TODO check why there are more then one same forms
            if (isset($this->forms[$formName])) {
                continue;
            }

            $this->forms[$formName] = $form;
        }
    }

    public function resetForms(): void
    {
        $this->actualForm = null;
        $this->formClassNames = [];
        foreach ($this->forms as $formName => $form) {
            $id = md5(uniqid());
            $className = ucfirst($formName) . '_' . $id;
            $this->formClassNames[$formName] = $className;
        }
    }
}
