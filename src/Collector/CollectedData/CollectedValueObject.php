<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\CollectedData;

abstract class CollectedValueObject
{
  /**
   * @return array<string, mixed>
   */
    abstract public function toArray(): array;
}
