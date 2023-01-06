<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;

interface LatteTemplateResolverInterface
{
    public function resolve(CollectedResolvedNode $resolvedNode, LatteContextData $latteContext): LatteTemplateResolverResult;
}
