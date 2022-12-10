<?php

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Type\TypeSerializer;

abstract class CollectedValueObject
{
  /**
   * @return array<string, mixed>
   */
    abstract public function toArray(TypeSerializer $typeSerializer): array;
}
