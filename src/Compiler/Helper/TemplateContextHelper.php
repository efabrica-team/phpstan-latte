<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Helper;

use Efabrica\PHPStanLatte\Template\Template;
use PHPStan\File\RelativePathHelper;

final class TemplateContextHelper
{
    private RelativePathHelper $relativePathHelper;

    public function __construct(RelativePathHelper $relativePathHelper)
    {
        $this->relativePathHelper = $relativePathHelper;
    }

    public function getContext(Template $template): string
    {
        $context = '';
        $actualClass = $template->getActualClass();
        if ($actualClass !== null) {
            $context .= $actualClass;
        }
        $actualAction = $template->getActualAction();
        if ($actualAction !== null) {
            $context .= '::' . $actualAction;
        } else {
            $context .= ' (standalone template, 💡see https://github.com/efabrica-team/phpstan-latte/blob/main/docs/how_it_works.md#variable-baz-might-not-be-defined Point 2 for more details)';
        }
        foreach ($template->getParentTemplatePaths() as $parentTemplate) {
            $context .= ' included in ' . $this->relativePathHelper->getRelativePath(realpath($parentTemplate) ?: '');
        }
        return $context;
    }
}
