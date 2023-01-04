<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

use Efabrica\PHPStanLatte\Template\Form\Form;

interface FormsNodeVisitorInterface
{
    /**
     * @param Form[] $forms
     */
    public function setForms(array $forms): void;

    /**
     * Creates form class names, will not be needed if we will have namespaces in compiled templates
     */
    public function resetForms(): void;
}
