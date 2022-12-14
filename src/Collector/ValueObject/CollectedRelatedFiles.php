<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Type\TypeSerializer;

/**
 * @phpstan-type CollectedRelatedFilesArray array{processedFile: string, relatedFiles: string[]}
 */
final class CollectedRelatedFiles extends CollectedValueObject
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
     * @phpstan-return CollectedRelatedFilesArray
     */
    public function toArray(TypeSerializer $typeSerializer): array
    {
        return [
            'processedFile' => $this->processedFile,
            'relatedFiles' => $this->relatedFiles,
        ];
    }

    /**
     * @phpstan-param CollectedRelatedFilesArray $item
     */
    public static function fromArray(array $item, TypeSerializer $typeSerializer): self
    {
        return new CollectedRelatedFiles($item['processedFile'], $item['relatedFiles']);
    }
}
