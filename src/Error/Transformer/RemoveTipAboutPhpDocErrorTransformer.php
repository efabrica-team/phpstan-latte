<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error\Transformer;

use Efabrica\PHPStanLatte\Error\Error;

/**
 * all variable types in compiled templates are from PHPDoc, so this tip doesn't make sense
 */
final class RemoveTipAboutPhpDocErrorTransformer implements ErrorTransformerInterface
{
    private const PHP_DOC_REGEX = '/Because the type is coming from a PHPDoc, you can turn off this check/';

    public function transform(Error $error): Error
    {
        if ($error->getTip() === null) {
            return $error;
        }

        if (preg_match(self::PHP_DOC_REGEX, $error->getTip()) === 1) {
            $error->setTip(null);
        }
        return $error;
    }
}
