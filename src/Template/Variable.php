<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

final class Variable
{
    private string $name;

    private Type $type;

    public function __construct(string $name, Type $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTypeAsString(): string
    {
        return $this->type->describe(VerbosityLevel::typeOnly());
    }
}
