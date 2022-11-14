<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error\Transformer;

use Efabrica\PHPStanLatte\Error\Error;

final class UndefinedLatteFilterErrorTransformer implements ErrorTransformerInterface
{
    /** @see https://regex101.com/r/vqPGnD/1 */
    private const UNDEFINED_FILTER_REGEX = '/Access to an undefined property Latte\\\\Runtime\\\\FilterExecutor::\\$(?<undefined_filter>.*?)\\./';

    public function transform(Error $error): Error
    {
        preg_match(self::UNDEFINED_FILTER_REGEX, $error->getMessage(), $match);
        if ($match !== [] && isset($match['undefined_filter'])) {
            $message = 'Undefined latte filter "' . $match['undefined_filter'] . '".';
            $tip = 'Register it in phpstan.neon: parameters > latte > filters. See https://github.com/efabrica-team/phpstan-latte#setup';
            $error->setMessage($message);
            $error->setTip($tip);
        }
        return $error;
    }
}
