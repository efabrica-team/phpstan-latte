<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Analyser\FileAnalyserFactory;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedRelatedFiles;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Collector;
use PHPStan\Collectors\Registry;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\DirectRegistry;

final class AdditionalDataCollector
{
    private FileAnalyserFactory $fileAnalyserFactory;

    /** @var AbstractCollector[] */
    private array $latteCollectors;

    /** @var AbstractCollector[] */
    private array $latteCollectorsForAnalyse;

    private TypeSerializer $typeSerializer;

    /**
     * @param AbstractCollector[] $latteCollectors
     */
    public function __construct(
        array $latteCollectors,
        FileAnalyserFactory $fileAnalyserFactory,
        TypeSerializer $typeSerializer
    ) {
        $this->latteCollectors = $latteCollectors;
        $this->latteCollectorsForAnalyse = array_filter($latteCollectors, function (AbstractCollector $latteCollector) {
            return !$latteCollector instanceof ResolvedNodeCollector;
        });
        $this->fileAnalyserFactory = $fileAnalyserFactory;
        $this->typeSerializer = $typeSerializer;
    }

    /**
     * @param string[] $newFilesToCheck
     */
    public function collect(CollectedDataNode $collectedDataNode, array $newFilesToCheck): CollectedDataNode
    {
        if ($newFilesToCheck === []) {
            return $collectedDataNode;
        }

        $collectedData = [$this->createCollectedDataFromNode($collectedDataNode)];
        foreach ($newFilesToCheck as $newFileToCheck) {
            $fileAnalyserResult = $this->fileAnalyserFactory->create()->analyseFile(
                $newFileToCheck,
                [],
                new DirectRegistry([]),
                new Registry($this->latteCollectorsForAnalyse),
                null
            );
            $collectedData[] = $fileAnalyserResult->getCollectedData();
        }

        $newCollectedDataNode = new CollectedDataNode(array_merge(...$collectedData));
        $collectedRelatedFiles = RelatedFilesCollector::loadData($newCollectedDataNode, $this->typeSerializer, CollectedRelatedFiles::class);

        $processedFiles = [];
        $relatedFiles = [];
        foreach ($collectedRelatedFiles as $collectedRelatedFile) {
            $processedFiles[] = $collectedRelatedFile->getProcessedFile();
            $relatedFiles[] = array_filter($collectedRelatedFile->getRelatedFiles(), function (string $file) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'php';
            });
        }

        $newFilesToCheck = array_diff(array_unique(array_merge(...$relatedFiles)), array_unique($processedFiles));
        return $this->collect($newCollectedDataNode, $newFilesToCheck);
    }

    /**
     * @return CollectedData[]
     */
    private function createCollectedDataFromNode(CollectedDataNode $collectedDataNode): array
    {
        $collectedData = [];
        foreach ($this->latteCollectors as $collector) {
            /** @var class-string<Collector<Node, mixed>> $collectorType */
            $collectorType = get_class($collector);
            $collectorData = $collectedDataNode->get($collectorType);
            foreach ($collectorData as $file => $fileData) {
                foreach ($fileData as $data) {
                    $collectedData[] = new CollectedData($data, $file, $collectorType);
                }
            }
        }
        return $collectedData;
    }
}
