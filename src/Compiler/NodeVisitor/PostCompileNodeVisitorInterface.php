<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use PhpParser\NodeVisitor;
use PHPStan\Analyser\Scope;

interface PostCompileNodeVisitorInterface extends NodeVisitor
{
    public function setScope(Scope $scope): void;
}
