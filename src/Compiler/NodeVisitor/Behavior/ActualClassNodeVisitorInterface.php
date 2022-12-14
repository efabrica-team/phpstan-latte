<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

interface ActualClassNodeVisitorInterface
{
    public function setActualClass(?string $actualClass): void;
}
