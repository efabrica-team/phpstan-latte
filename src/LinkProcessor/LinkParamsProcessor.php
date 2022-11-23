<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LinkProcessor;

use InvalidArgumentException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use ReflectionMethod;

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
            $key = $arrayItem->key;
            if ($key instanceof String_) {  // ignore key for now
                $arrayItem = new ArrayItem($arrayItem->value, null, $arrayItem->byRef, $arrayItem->getAttributes());
                $transferredParams[$key->value] = new Arg($arrayItem);
                continue;
            }
            $transferredParams[] = new Arg($arrayItem);
        }

        $i = 0;
        $reflectionMethod = new ReflectionMethod($class, $method);
        $methodParameters = [];
        foreach ($reflectionMethod->getParameters() as $param) {
            $name = $param->getName();
            $methodParameters[] = $name;
            if (array_key_exists($i, $transferredParams)) {
                $transferredParams[$name] = $transferredParams[$i];
                unset($transferredParams[$i]);
                $i++;
            }
        }

        return array_filter(array_replace(array_flip($methodParameters), $transferredParams), function ($param) {
            return $param instanceof Arg;
        });
    }
}
