<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;

interface CustomLatteTemplateResolverInterface extends LatteTemplateResolverInterface
{
    /**
     * @return CollectedResolvedNode[]
     */
    public function collect(): array;
}
