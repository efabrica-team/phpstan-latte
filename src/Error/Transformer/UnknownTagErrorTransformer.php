<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error\Transformer;

use Efabrica\PHPStanLatte\Error\Error;

final class UnknownTagErrorTransformer implements ErrorTransformerInterface
{
    private const UNKNOWN_TAG_REGEX = '/Unknown tag {(?<unknown_tag>.*)}/';

    public function transform(Error $error): Error
    {
        preg_match(self::UNKNOWN_TAG_REGEX, $error->getMessage(), $match);
        if (isset($match['unknown_tag'])) {
            $tip = 'Register the method to install this tag/macro in phpstan.neon: parameters > latte > macros. See https://github.com/efabrica-team/phpstan-latte#setup';
            $error->setTip($tip);
        }
        return $error;
    }
}
