<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

use PHPStan\Type\Type;

interface NameTypeItem extends NameItem
{
    public function getType(): Type;

    public function getTypeAsString(): string;
}
