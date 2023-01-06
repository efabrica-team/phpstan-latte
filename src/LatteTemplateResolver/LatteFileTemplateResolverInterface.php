<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;

interface LatteFileTemplateResolverInterface extends LatteTemplateResolverInterface
{
    /** Try collect node in actual scope */
    public function collect(string $templateFile): ?CollectedResolvedNode;
}
