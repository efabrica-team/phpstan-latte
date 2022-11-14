<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error\Transformer;

use Efabrica\PHPStanLatte\Error\Error;

interface ErrorTransformerInterface
{
    public function transform(Error $error): Error;
}
