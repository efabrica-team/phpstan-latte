<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

use Exception;
use PHPStan\Analyser\Scope;

trait ScopeNodeVisitorBehavior
{
    private ?Scope $scope = null;

    public function setScope(Scope $scope): void
    {
        $this->scope = $scope;
    }

    public function getScope(): Scope
    {
        if ($this->scope === null) {
            throw new Exception('Scope has not been set');
        }
        return $this->scope;
    }
}
