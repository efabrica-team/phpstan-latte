<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

use PHPStan\PhpDoc\TypeStringResolver;
use ReturnTypeWillChange;

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

    /**
     * @return array<string, mixed>
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'processedFile' => $this->processedFile,
            'relatedFiles' => $this->relatedFiles,
        ];
    }

    /**
     * @param array{processedFile: string, relatedFiles: string[]} $data
     */
    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): static
    {
        return new self(
            $data['processedFile'],
            $data['relatedFiles']
        );
    }
}
