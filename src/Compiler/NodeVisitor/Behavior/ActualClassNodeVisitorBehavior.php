<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

trait ActualClassNodeVisitorBehavior
{
    private ?string $actualClass;

    public function setActualClass(?string $actualClass): void
    {
        $this->actualClass = $actualClass;
    }
}
