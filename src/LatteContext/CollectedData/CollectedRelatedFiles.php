<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

final class CollectedRelatedFiles extends CollectedLatteContextObject
{
    private string $processedFile;

    /** @var string[] */
    private array $relatedFiles;

    /**
     * @param string[] $relatedFiles
     */
    public function __construct(string $processedFile, array $relatedFiles)
    {
        $this->processedFile = $processedFile;
        $this->relatedFiles = $relatedFiles;
    }

    public function getProcessedFile(): string
    {
        return $this->processedFile;
    }

    /**
     * @return string[]
     */
    public function getRelatedFiles(): array
    {
        return $this->relatedFiles;
    }
}
