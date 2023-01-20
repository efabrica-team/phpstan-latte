<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

use Efabrica\PHPStanLatte\Template\Component;

interface ComponentsNodeVisitorInterface
{
    /**
     * @param Component[] $components
     */
    public function setComponents(array $components): void;
}
