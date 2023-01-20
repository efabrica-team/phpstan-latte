<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LinkProcessor;

use InvalidArgumentException;
use LogicException;
use PhpParser\BuilderHelpers;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PHPStan\BetterReflection\BetterReflection;

final class LinkParamsProcessor
{
    /**
     * @param Arg[] $params
     * @return Arg[]
     */
    public function process(string $class, string $method, array $params): array
    {
        if ($class === '') {
            throw new InvalidArgumentException('Empty class name');
        }

        if ($method === '') {
            throw new InvalidArgumentException('Empty method name');
        }

        if (count($params) > 1) {
            throw new InvalidArgumentException('Too many parameters');
        }

        $reflectionClass = (new BetterReflection())->reflector()->reflectClass($class);
        $reflectionMethod = $reflectionClass->getMethod($method);
        if ($reflectionMethod === null) {
            throw new InvalidArgumentException("Method $class::$method not found");
        }

        $methodParameters = [];
        foreach ($reflectionMethod->getParameters() as $param) {
            $methodParameters[] = $param->getName();
        }

        $transferredParams = [];
        if ($params !== []) {
            $paramValue = $params[0]->value;
            if (!$paramValue instanceof Array_) {
                throw new InvalidArgumentException('Wrong type of parameter value');
            }

            foreach ($paramValue->items as $arrayItem) {
                if (!$arrayItem instanceof ArrayItem) {
                    continue;
                }
                $key = $arrayItem->key;
                if ($key instanceof String_) {
                    $transferredParamName = $key->value;
                    // Skip named params which are not method params - Nette adds them to query params (e.g. ?param1=foo&param2=bar) and not check if they are in method
                    if (!in_array($transferredParamName, $methodParameters, true)) {
                        continue;
                    }
                    $arrayItem = new ArrayItem($arrayItem->value, null, $arrayItem->byRef, $arrayItem->getAttributes());
                    $transferredParams[$key->value] = new Arg($arrayItem);
                    continue;
                }
                $transferredParams[] = new Arg($arrayItem);
            }
        }

        $i = 0;
        foreach ($reflectionMethod->getParameters() as $param) {
            $name = $param->getName();
            $type = (string) $param->getType();
            if (array_key_exists($i, $transferredParams)) {
                $transferredParams[$name] = $transferredParams[$i];
                unset($transferredParams[$i]);
                $i++;
            } elseif (array_key_exists($name, $transferredParams)) {
                continue;
            } elseif ($param->isDefaultValueAvailable()) {
                try {
                    $transferredParams[$name] = new Arg(BuilderHelpers::normalizeValue($param->getDefaultValue()));
                } catch (LogicException $e) {
                }
            } elseif ($type === 'array' || $type === 'iterable') {
                $transferredParams[$name] = new Arg(BuilderHelpers::normalizeValue([]));
            } else {
                $transferredParams[$name] = new Arg(BuilderHelpers::normalizeValue(null));
            }
        }

        return array_filter(array_replace(array_flip($methodParameters), $transferredParams), function ($param) {
            return $param instanceof Arg;
        });
    }
}
