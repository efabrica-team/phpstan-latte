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
}
