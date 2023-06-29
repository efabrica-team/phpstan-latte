<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error\Transformer;

use Efabrica\PHPStanLatte\Error\Error;

final class UndefinedVariableTransformer implements ErrorTransformerInterface
{
    private const UNDEFINED_VARIABLE_REGEX = '/Undefined variable: \$(?<undefined_variable>.*)/';

    public function transform(Error $error): Error
    {
        preg_match(self::UNDEFINED_VARIABLE_REGEX, $error->getMessage(), $match);
        if (isset($match['undefined_variable'])) {
            $message = 'Variable $' . $match['undefined_variable'] . ' might not be defined.';
            $error->setMessage($message);
        }
        return $error;
    }
}
