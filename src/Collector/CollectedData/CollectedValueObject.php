<?php

namespace Efabrica\PHPStanLatte\Collector\CollectedData;

abstract class CollectedValueObject
{
  /**
   * @return array<string, mixed>
   */
    abstract public function toArray(): array;
}
