<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LinkProcessor;

use InvalidArgumentException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;

final class LinkParamsProcessor
{
    /**
     * @param Arg[] $params
     * @return Arg[]
     */
    public function process(string $class, string $method, array $params): array
    {
        if ($params === []) {
            return [];
        }

        if (count($params) > 1) {
            throw new InvalidArgumentException('Too many parameters');
        }

        $paramValue = $params[0]->value;
        if (!$paramValue instanceof Array_) {
            throw new InvalidArgumentException('Wrong type of parameter value');
        }

        $transferredParams = [];
        foreach ($paramValue->items as $arrayItem) {
            if (!$arrayItem instanceof ArrayItem) {
                continue;
            }

            if ($arrayItem->key !== null) {  // ignore key for now
                $arrayItem = new ArrayItem($arrayItem->value, null, $arrayItem->byRef, $arrayItem->getAttributes());
            }
            $transferredParams[] = new Arg($arrayItem);
        }

        return $transferredParams;
    }
}
