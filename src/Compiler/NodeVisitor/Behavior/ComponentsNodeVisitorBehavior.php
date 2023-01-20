<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

use Efabrica\PHPStanLatte\Template\Component;

trait ComponentsNodeVisitorBehavior
{
    /** @var Component[] */
    private array $components = [];

    /**
     * @param Component[] $components
     */
    public function setComponents(array $components): void
    {
        $this->components = $components;
    }

    /**
     * @param Component[] $components
     */
    private function findComponentByName(array $components, string $componentName): ?Component
    {
        $componentNameParts = explode('-', $componentName);
        $componentNamePart = array_shift($componentNameParts);
        foreach ($components as $component) {
            if ($component->getName() !== $componentNamePart) {
                continue;
            }
            if (count($componentNameParts) === 0) {
                return $component;
            }
            return $this->findComponentByName($component->getSubcomponents() ?: [], implode('-', $componentNameParts));
        }
        return null;
    }
}
