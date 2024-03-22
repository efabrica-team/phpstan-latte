<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LinkProcessor;

use Nette\Application\PresenterFactory;
use Nette\InvalidStateException;

final class FakePresenterFactory extends PresenterFactory
{
    /** @var array<string, array{string, string, string}> */
    private array $mapping = [];

    /**
     * @param array<string, string|array{string, string}|array{string, string, string}> $mapping
     */
    public function setFakeMapping(array $mapping): FakePresenterFactory
    {
        parent::setMapping($mapping);
        foreach ($mapping as $module => $mask) {
            if (is_string($mask)) {
                if (!preg_match('#^\\\\?([\w\\\\]*\\\\)?(\w*\*\w*?\\\\)?([\w\\\\]*\*\w*)$#D', $mask, $m)) {
                    throw new InvalidStateException("Invalid mapping mask '$mask'.");
                }

                $this->mapping[$module] = [$m[1], $m[2] ?: '*Module\\', $m[3]];
            } elseif (is_array($mask) && count($mask) === 3) {
                $this->mapping[$module] = [$mask[0] ? $mask[0] . '\\' : '', $mask[1] . '\\', $mask[2]];
            } else {
                throw new InvalidStateException("Invalid mapping mask for module $module.");
            }
        }

        return $this;
    }

    public function unformatPresenterClass(string $class): ?string
    {
        foreach ($this->mapping as $module => $mapping) {
            $mapping = str_replace(['\\', '*'], ['\\\\', '(\w+)'], $mapping);
            if (preg_match('#^\\\\?' . $mapping[0] . '((?:' . $mapping[1] . ')*)' . $mapping[2] . '$#Di', $class, $matches) !== 1) {
                continue;
            }

            return ($module === '*' ? '' : $module . ':')
              . preg_replace('#' . $mapping[1] . '#iA', '$1:', $matches[1]) . $matches[3];
        }
        return null;
    }
}
