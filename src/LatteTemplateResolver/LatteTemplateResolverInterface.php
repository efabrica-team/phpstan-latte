<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Template;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

interface LatteTemplateResolverInterface
{
    /** Checks if actual resolver can resolve node in actual scope */
    public function check(Node $node, Scope $scope): bool;

    /**
     * @return Template[]
     */
    public function findTemplatesWithParameters(Node $node, Scope $scope): array;

    /**
     * @return Component[]
     */
    public function findComponents(Node $node, Scope $scope): array;
}
