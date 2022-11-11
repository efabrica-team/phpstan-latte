<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LinkProcessor;

final class LinkProcessorFactory
{
    /** @var LinkProcessorInterface[] */
    private array $linkProcessors;

    /**
     * @param LinkProcessorInterface[] $linkProcessors
     */
    public function __construct(array $linkProcessors)
    {
        $this->linkProcessors = $linkProcessors;
    }

    public function create(string $targetName): ?LinkProcessorInterface
    {
        foreach ($this->linkProcessors as $linkProcessor) {
            if ($linkProcessor->check($targetName)) {
                return $linkProcessor;
            }
        }

        return null;
    }
}
