<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LinkProcessor;

use Efabrica\PHPStanLatte\Type\TypeHelper;
use InvalidArgumentException;
use PhpParser\BuilderHelpers;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PHPStan\Reflection\ReflectionProvider;
use function array_filter;
use function array_flip;
use function array_key_exists;
use function array_replace;
use function count;
use function in_array;

final class LinkParamsProcessor
{
    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

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

        $classReflection = $this->reflectionProvider->getClass($class);
        if (!$classReflection->hasNativeMethod($method)) {
            throw new InvalidArgumentException("Method $class::$method not found");
        }

        $methodReflection = $classReflection->getNativeMethod($method);
        $variants = $methodReflection->getVariants();
        if ($variants === []) {
            throw new InvalidArgumentException("Method $class::$method has no variants");
        }

        $parameters = $variants[0]->getParameters();

        $methodParameters = [];
        foreach ($parameters as $param) {
            $methodParameters[] = $param->getName();
        }

        $transferredParams = [];
        if ($params !== []) {
            $paramValue = $params[0]->value;
            if (!$paramValue instanceof Array_) {
                throw new InvalidArgumentException('Wrong type of parameter value');
            }

            foreach ($paramValue->items as $arrayItem) {
                $key = $arrayItem->key;
                if ($key instanceof String_) {
                    $transferredParamName = $key->value;
                    // Skip named params which are not method params - Nette adds them to query params (e.g. ?param1=foo&param2=bar) and not check if they are in method
                    if (!in_array($transferredParamName, $methodParameters, true)) {
                        continue;
                    }
                    $arrayItem = new ArrayItem($arrayItem->value, null, $arrayItem->byRef, $arrayItem->getAttributes());
                    $transferredParams[$key->value] = new Arg($arrayItem->value);
                    continue;
                }
                $transferredParams[] = new Arg($arrayItem->value);
            }
        }

        $i = 0;
        foreach ($parameters as $param) {
            $name = $param->getName();
            $type = $param->getType();
            $defaultValueType = $param->getDefaultValue();
            if (array_key_exists($i, $transferredParams)) {
                $transferredParams[$name] = $transferredParams[$i];
                unset($transferredParams[$i]);
                $i++;
            } elseif (array_key_exists($name, $transferredParams)) {
                continue;
            } elseif ($defaultValueType !== null) {
                $transferredParams[$name] = new Arg(BuilderHelpers::normalizeValue(TypeHelper::typeToValue($defaultValueType)));
            } elseif ($type->isArray()->yes() || $type->isIterable()->yes()) {
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
