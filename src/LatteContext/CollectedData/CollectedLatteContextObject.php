<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

use JsonSerializable;
use PHPStan\PhpDoc\TypeStringResolver;

abstract class CollectedLatteContextObject implements JsonSerializable
{
    /**
     * @param array<mixed> $data
     */
    abstract public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self;
}
