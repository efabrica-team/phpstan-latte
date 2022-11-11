<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

use PHPStan\Analyser\Scope;

trait ScopedNodeVisitorBehavior
{
    private Scope $scope;

    public function setScope(Scope $scope): void
    {
        $this->scope = $scope;
    }
}
