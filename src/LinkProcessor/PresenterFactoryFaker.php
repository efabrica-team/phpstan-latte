<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LinkProcessor;

use Nette\Application\PresenterFactory;

final class PresenterFactoryFaker
{
    /** @var array<string, string> */
    private array $mapping;

    /**
     * @param array<string, string> $mapping
     */
    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getPresenterFactory(): PresenterFactory
    {
        $presenterFactory = new PresenterFactory();
        $presenterFactory->setMapping($this->mapping);
        return $presenterFactory;
    }
}
