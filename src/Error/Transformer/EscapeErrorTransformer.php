<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error\Transformer;

use Efabrica\PHPStanLatte\Error\Error;
use Nette\Utils\Strings;

final class EscapeErrorTransformer implements ErrorTransformerInterface
{
    private const HTML_OUTPUT_REGEX = '/Parameter #1 \$s of static method Latte\\\\(Runtime|Essentials)\\\\Filters::escape(?<escape>.*)\(\) expects [^ ]+, (?<type>.*) given\./';

    public function transform(Error $error): Error
    {
        preg_match(self::HTML_OUTPUT_REGEX, $error->getMessage(), $match);
        if (isset($match[0]) && isset($match['type'])) {
            $escape = Strings::upper($match['escape'] ?? '');
            if ($escape === 'HTMLTEXT') {
                $escape = 'HTML';
            }
            $message = "Cannot convert {$match['type']} to {$escape} string.";
            $error->setMessage($message);
        }
        return $error;
    }
}
