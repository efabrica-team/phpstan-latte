<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error\Transformer;

use Efabrica\PHPStanLatte\Error\Error;

final class BlockParameterErrorTransformer implements ErrorTransformerInterface
{
    private const BLOCK_METHOD = '/method (.*?)::block(?<block>.*)\(\)/i';

    public function transform(Error $error): Error
    {
        preg_match(self::BLOCK_METHOD, $error->getMessage(), $match);
        if (isset($match['block']) && strpos($error->getMessage(), 'MissingBlockParameter') === false) {
            $block = lcfirst(str_replace('_', '-', $match['block']));
            $message = ucfirst(str_replace($match[0], 'block ' . $block, $error->getMessage()));
            $error->setMessage($message);
        }
        return $error;
    }
}
