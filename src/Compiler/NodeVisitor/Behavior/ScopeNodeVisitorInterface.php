<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

use PHPStan\Analyser\Scope;

interface ScopeNodeVisitorInterface
{
    public function setScope(Scope $scope): void;
}
