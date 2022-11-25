<div n:if="count($items)">
    {foreach $items as $item}
        {$item->title}
    {/foreach}
</div>
-----
<?php

use Latte\Runtime as LR;
final class PHPStanLatteTemplate extends Latte\Runtime\Template
{
    public function main() : array
    {
        extract($this->params);
        /** @var string $baseUrl */
        /** @var string $basePath */
        /** @var Latte\Runtime\FilterInfo $ʟ_fi */
        /** @var Nette\Security\User $user */
        /** @var Nette\Application\UI\Presenter $presenter */
        /** @var Nette\Application\UI\Control $control */
        /** @var array<stdClass> $flashes */
        /** @var Nette\Localization\Translator $netteLocalizationTranslatorFilter */
        /* line 1 */
        if (count($items)) {
            echo '<div>
    ';
            $iterations = 0;
            /* line 2 */
            foreach ($items as $item) {
                echo '
        ';
                /* line 3 */
                echo LR\Filters::escapeHtmlText($item->title);
                echo "\n";
                $iterations++;
            }
            echo '</div>';
        }
        return get_defined_vars();
    }
    public function prepare() : void
    {
        extract($this->params);
        /** @var string $baseUrl */
        /** @var string $basePath */
        /** @var Latte\Runtime\FilterInfo $ʟ_fi */
        /** @var Nette\Security\User $user */
        /** @var Nette\Application\UI\Presenter $presenter */
        /** @var Nette\Application\UI\Control $control */
        /** @var array<stdClass> $flashes */
        /** @var Nette\Localization\Translator $netteLocalizationTranslatorFilter */
        if ($this->getReferringTemplate() === null || $this->getReferenceType() === "extends") {
            foreach (array_intersect_key(['item' => '2'], $this->params) as $ʟ_v => $ʟ_l) {
                trigger_error("Variable \${$ʟ_v} overwritten in foreach on line {$ʟ_l}");
            }
        }
        Nette\Bridges\ApplicationLatte\UIRuntime::initialize($this, $this->parentName, $this->blocks);
    }
}
-----
<?php

use Latte\Runtime as LR;
final class PHPStanLatteTemplate extends Latte\Runtime\Template
{
    public function main(array $ʟ_args) : void
    {
        extract($this->params);
        /** @var string $baseUrl */
        /** @var string $basePath */
        /** @var Latte\Runtime\FilterInfo $ʟ_fi */
        /** @var Nette\Security\User $user */
        /** @var Nette\Application\UI\Presenter $presenter */
        /** @var Nette\Application\UI\Control $control */
        /** @var array<stdClass> $flashes */
        /** @var Nette\Localization\Translator $netteLocalizationTranslatorFilter */
        unset($ʟ_args);
        if ($this->global->snippetDriver?->renderSnippets($this->blocks[self::LayerSnippet], $this->params)) {
            return;
        }
        /* line 1 */
        if (count($items)) {
            echo '<div>
';
            /* line 2 */
            foreach ($items as $item) {
                echo '        ';
                /* line 3 */
                echo LR\Filters::escapeHtmlText($item->title);
                echo "\n";
            }
            echo '</div>';
        }
    }
    public function prepare() : array
    {
        extract($this->params);
        /** @var string $baseUrl */
        /** @var string $basePath */
        /** @var Latte\Runtime\FilterInfo $ʟ_fi */
        /** @var Nette\Security\User $user */
        /** @var Nette\Application\UI\Presenter $presenter */
        /** @var Nette\Application\UI\Control $control */
        /** @var array<stdClass> $flashes */
        /** @var Nette\Localization\Translator $netteLocalizationTranslatorFilter */
        if ($this->getReferringTemplate() === null || $this->getReferenceType() === 'extends') {
            foreach (array_intersect_key(['item' => '2'], $this->params) as $ʟ_v => $ʟ_l) {
                trigger_error("Variable \${$ʟ_v} overwritten in foreach on line {$ʟ_l}");
            }
        }
        return get_defined_vars();
    }
}