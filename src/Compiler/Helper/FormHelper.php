<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Helper;

use Nette\Forms\Form;

final class FormHelper
{
    /**
     * @template T of Form
     * @param class-string<T> $form
     * @return T
     */
    public static function getForm(string $form): Form
    {
        return new Form(); // irrelevant, only typehint is important
    }
}
