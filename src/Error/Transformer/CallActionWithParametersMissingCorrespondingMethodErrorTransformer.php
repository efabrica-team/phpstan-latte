<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error\Transformer;

use Efabrica\PHPStanLatte\Error\Error;

final class CallActionWithParametersMissingCorrespondingMethodErrorTransformer implements ErrorTransformerInterface
{
    private const CALL_ACTION_WITH_PARAMETERS_REGEX = '/Method (?<presenter>.*)::(?<method>.*)WithParametersMissingCorrespondingMethod not found/';

    public function transform(Error $error): Error
    {
        preg_match(self::CALL_ACTION_WITH_PARAMETERS_REGEX, $error->getMessage(), $match);
        if (isset($match['presenter']) && isset($match['method'])) {
            $message = 'Invalid link: Unable to pass parameters to "' . $match['presenter'] . '::' . $match['method'] . '()", missing corresponding method.';
            $tip = 'Add method action' . ucfirst($match['method']) . ' or render' . ucfirst($match['method']) . ' with corresponding parameters to presenter ' . $match['presenter'];
            $error->setMessage($message);
            $error->setTip($tip);
        }
        return $error;
    }
}
