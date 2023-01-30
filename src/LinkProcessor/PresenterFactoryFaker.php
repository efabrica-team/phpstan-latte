<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LinkProcessor;

use InvalidArgumentException;
use Nette\Application\IPresenterFactory;
use Nette\Application\PresenterFactory;

final class PresenterFactoryFaker
{
    /** @var array<string, string> */
    private array $mapping;

    private ?string $presenterFactoryBootstrap;

    private ?IPresenterFactory $presenterFactory = null;

    /**
     * @param array<string, string> $mapping
     */
    public function __construct(array $mapping, ?string $presenterFactoryBootstrap)
    {
        $this->mapping = $mapping;
        $this->presenterFactoryBootstrap = $presenterFactoryBootstrap;
    }

    public function getPresenterFactory(): ?IPresenterFactory
    {
        $presenterFactory = null;
        if ($this->presenterFactoryBootstrap !== null) {
            $presenterFactory = require $this->presenterFactoryBootstrap;
            if (!$presenterFactory instanceof IPresenterFactory) {
                throw new InvalidArgumentException('Presenter factory file must return instance of Nette\Application\IPresenterFactory');
            }
        } elseif ($this->mapping !== []) {
            $presenterFactory = new PresenterFactory();
            $presenterFactory->setMapping($this->mapping);
        }

        $this->presenterFactory = $presenterFactory;
        return $this->presenterFactory;
    }
}
