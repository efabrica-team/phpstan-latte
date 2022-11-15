<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error\Transformer;

use Efabrica\PHPStanLatte\Error\Error;

final class ComponentDoesntExist implements ErrorTransformerInterface
{

    private const COMPONENT_WITH_NAME_DOESNT_EXIST_REGEX = '/PHPDoc tag \@var for variable \$_tmp contains unknown class ComponentWithName(?<component_name>.*)DoesntExist\./';

    public function transform(Error $error): Error
    {
        preg_match(self::COMPONENT_WITH_NAME_DOESNT_EXIST_REGEX, $error->getMessage(), $match);
        if (isset($match['component_name'])) {
            $error->setMessage('Component with name "' . $match['component_name'] . '" probably doesn\'t exist.');
            $error->setTip(null);   // TODO create some tip
        }
        return $error;
    }
}
