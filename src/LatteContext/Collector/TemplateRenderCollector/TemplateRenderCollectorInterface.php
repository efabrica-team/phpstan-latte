<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\TemplateRenderCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\LatteContext\Collector\LatteContextSubCollectorInterface;

/**
 * @uses CollectedTemplateRender
 * @extends LatteContextSubCollectorInterface<CollectedTemplateRender>
 */
interface TemplateRenderCollectorInterface extends LatteContextSubCollectorInterface
{
}
