<?php

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

abstract class CollectedValueObject
{
  /**
   * @return array<string, mixed>
   */
    abstract public function toArray(): array;
}
