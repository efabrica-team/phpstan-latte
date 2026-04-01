<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error\Transformer;

use Efabrica\PHPStanLatte\Error\Error;
use function lcfirst;
use function preg_match;
use function str_replace;
use function ucfirst;

final class BlockParameterErrorTransformer implements ErrorTransformerInterface
{
    private const BLOCK_METHOD = '/method (.*?)::block(?<block>.*)\(\)/i';

    public function transform(Error $error): Error
    {
        if (preg_match(self::BLOCK_METHOD, $error->getMessage(), $match) === 1) {
            $block = lcfirst(str_replace('_', '-', $match['block']));
            $message = $error->getMessage();
            // replace method name to block name
            $message = str_replace($match[0], 'block ' . $block, $message);
            // replace fake `MissingBlockParameter` with `none` type
            $message = str_replace('MissingBlockParameter', 'none', $message);
            $error->setMessage(ucfirst($message));
        }
        return $error;
    }
}
