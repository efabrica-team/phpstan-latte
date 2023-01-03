<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedForm;

interface FormsNodeVisitorInterface
{
    /**
     * @param CollectedForm[] $forms
     */
    public function setForms(array $forms): void;

    /**
     * Creates form class names, will not be needed if we will have namespaces in compiled templates
     */
    public function resetForms(): void;
}
