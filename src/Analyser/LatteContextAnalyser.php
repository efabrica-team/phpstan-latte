<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Analyser;

use Composer\InstalledVersions;
use Efabrica\PHPStanLatte\LatteContext\Collector\AbstractLatteContextCollector;
use Efabrica\PHPStanLatte\Temp\TempDirResolver;
use Exception;
use InvalidArgumentException;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use PhpParser\Node;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\File\FileHelper;
use PHPStan\Parser\Parser;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;
use RuntimeException;
use Throwable;

final class LatteContextAnalyser
{
    private ScopeFactory $scopeFactory;

    private NodeScopeResolver $nodeScopeResolver;

    private Parser $parser;

    private TypeStringResolver $typeStringResolver;

    private ReflectionProvider $reflectionProvider;

    private FileHelper $fileHelper;

    private LatteContextCollectorRegistry $collectorRegistry;

    private string $tmpDir;

    /**
     * @param AbstractLatteContextCollector[] $collectors
     */
    public function __construct(
        ScopeFactory $scopeFactory,
        NodeScopeResolver $nodeScopeResolver,
        ReflectionProvider $reflectionProvider,
        FileHelper $fileHelper,
        Parser $parser,
        TypeStringResolver $typeStringResolver,
        TempDirResolver $tempDirResolver,
        array $collectors,
        bool $debugMode = false
    ) {
        $this->scopeFactory = $scopeFactory;
        $this->nodeScopeResolver = clone $nodeScopeResolver;
        $this->reflectionProvider = $reflectionProvider;
        $this->fileHelper = $fileHelper;
        // $this->nodeScopeResolver->setAnalysedFiles(null); TODO when changes in PHPStan are merged
        $this->parser = $parser;
        $this->typeStringResolver = $typeStringResolver;
        $this->collectorRegistry = new LatteContextCollectorRegistry($collectors);
        $this->tmpDir = $tempDirResolver->resolveCollectorDir();
        if (file_exists($this->tmpDir) && $debugMode) {
            FileSystem::delete($this->tmpDir);
        }
    }

    /**
     * @param string[] $files
     */
    public function analyseFiles(array $files): LatteContextData
    {
        $errors = [];
        $collectedData = [];
        $processedFiles = [];
        $counter = 0;

        $this->nodeScopeResolver->setAnalysedFiles($files); // TODO when changes in PHPStan are merged

        do {
            if ($counter++ > 100) {
                throw new RuntimeException('Infinite loop detected in LatteContextAnalyser.');
            }
            $relatedFiles = [];
            foreach ($files as $file) {
                $fileResult = $this->loadLatteContextDataFromCache($file);
                if (!$fileResult) {
                    $fileResult = $this->analyseFile($file);
                    if ($fileResult->getErrors() === []) {
                        $this->saveLatteContextDataToCache($file, $fileResult);
                    } else {
                        $errors = array_merge($errors, $fileResult->getErrors());
                    }
                } else {
                }
                if ($fileResult->getAllCollectedData() !== []) {
                    $collectedData = array_merge($collectedData, $fileResult->getAllCollectedData());
                    $processedFiles = array_unique(array_merge($processedFiles, $fileResult->getProcessedFiles()));
                    $relatedFiles = array_unique(array_merge($relatedFiles, $fileResult->getRelatedFiles()));
                }
            }
            $files = array_diff($relatedFiles, $processedFiles);
        } while (count($files) > 0);

        return new LatteContextData($collectedData, $errors);
    }

    public function analyseFile(string $file): LatteContextData
    {
        $fileErrors = [];
        $fileCollectedData = [];
        if (is_file($file)) {
            try {
                $parserNodes = $this->parser->parseFile($file);
                $nodeCallback = function (Node $node, Scope $scope) use ($file, &$fileErrors, &$fileCollectedData): void {
                    // TODO when changes in PHPStan are merged
                    if ($node instanceof TraitUse) {
                        $this->nodeScopeResolver->setAnalysedFiles($this->getTraitFiles($node));
                    }
                    $collectors = $this->collectorRegistry->getCollectorsForNode($node);
                    foreach ($collectors as $collector) {
                        try {
                            $collectedData = $collector->collectData($node, $scope);
                        } catch (Throwable $e) {
                            $fileErrors[] = RuleErrorBuilder::message(get_class($collector) . ' error: ' . $e->getMessage())
                                ->identifier('latte.collectorError')
                                ->file($file)
                                ->line($node->getLine())
                                ->build();
                            continue;
                        }
                        if ($collectedData === null || $collectedData === []) {
                            continue;
                        }
                        $fileCollectedData = array_merge($fileCollectedData, $collectedData);
                    }
                };
                $scope = $this->scopeFactory->create(ScopeContext::create($file));
                $this->nodeScopeResolver->processNodes($parserNodes, $scope, $nodeCallback);
            } catch (Throwable $e) {
                $fileErrors[] = RuleErrorBuilder::message('LatteContextAnalyser error: ' . $e->getMessage())
                    ->identifier('latte.failed')
                    ->file($file)
                    ->build();
            }
        } elseif (is_dir($file)) {
            $fileErrors[] = RuleErrorBuilder::message(sprintf('File %s is a directory.', $file))
                ->identifier('latte.fileError')
                ->file($file)
                ->build();
        } else {
            $fileErrors[] = RuleErrorBuilder::message(sprintf('File %s does not exist.', $file))
                ->identifier('latte.fileError')
                ->file($file)
                ->build();
        }
        return new LatteContextData($fileCollectedData, $fileErrors);
    }

    /**
     * TODO when changes in PHPStan are merged
     * @return string[]
     */
    private function getTraitFiles(TraitUse $node): array
    {
        $files = [];
        foreach ($node->traits as $trait) {
            $traitName = (string)$trait;
            if (!$this->reflectionProvider->hasClass($traitName)) {
                continue;
            }
            $traitReflection = $this->reflectionProvider->getClass($traitName);
            $traitFileName = $traitReflection->getFileName();
            if ($traitFileName !== null) {
                $files[] = $this->fileHelper->normalizePath($traitFileName);
            }
        }
        return $files;
    }

    /**
     * @param AbstractLatteContextCollector[] $collectors
     */
    public function withCollectors(array $collectors): self
    {
        $clone = clone $this;
        $clone->collectorRegistry = new LatteContextCollectorRegistry($collectors);
        return $clone;
    }

    private function cacheFilename(string $file): string
    {
        $cacheKey = md5(
            $file .
            PHP_VERSION_ID .
            (class_exists(InstalledVersions::class) ? json_encode(InstalledVersions::getAllRawData()) : '')
        );
        return $this->tmpDir . basename($file) . '.' . $cacheKey . '.json';
    }

    private function saveLatteContextDataToCache(string $file, LatteContextData $fileResult): void
    {
        if (!is_dir($this->tmpDir)) {
            Filesystem::createDir($this->tmpDir, 0777);
        }

        $cacheFile = $this->cacheFilename($file);

        try {
            $data = $fileResult->jsonSerialize();
        } catch (InvalidArgumentException $e) {
            // Cannot serialize data, skip caching
            if (is_file($cacheFile)) {
                FileSystem::delete($cacheFile);
            }
            return;
        }

        $cacheData = [
            'file' => $file,
            'fileHash' => sha1(Filesystem::read($file)),
            'data' => $data,
        ];
        foreach ($fileResult->getRelatedFiles() as $relatedFile) {
            $cacheData['dependencies'][] = [
                'file' => $relatedFile,
                'fileHash' => sha1(Filesystem::read($relatedFile)),
            ];
        }
        Filesystem::write(
            $cacheFile,
            Json::encode($cacheData, true)
        );
    }

    private function loadLatteContextDataFromCache(string $file): ?LatteContextData
    {
        $cacheFile = $this->cacheFilename($file);
        if (!is_file($cacheFile)) {
            return null;
        }

        try {
            $cacheData = Json::decode(Filesystem::read($cacheFile), true);
        } catch (Exception) {
            FileSystem::delete($cacheFile);
            return null;
        }

        if (!is_array($cacheData) || !isset($cacheData['file'], $cacheData['fileHash'], $cacheData['data'])) {
            FileSystem::delete($cacheFile);
            return null;
        }

        $file = $cacheData['file'];
        $fileHash = $cacheData['fileHash'];

        if (!is_string($file) || !is_string($fileHash)) {
            FileSystem::delete($cacheFile);
            return null;
        }

        // Check if the file has changed since the cache was created
        if (sha1(Filesystem::read($file)) !== $fileHash) {
            return null;
        }

        if (isset($cacheData['dependencies']) && is_array($cacheData['dependencies'])) {
            foreach ($cacheData['dependencies'] as $dependency) {
                if (!is_array($dependency) || !isset($dependency['file'], $dependency['fileHash'])) {
                    return null;
                }
                $dependencyFile = $dependency['file'];
                $dependencyFileHash = $dependency['fileHash'];
                if (!is_string($dependencyFile) || !is_string($dependencyFileHash)) {
                    return null;
                }
                if (!is_file($dependencyFile)) {
                    return null;
                }
                // Check if the dependency file has changed since the cache was created
                if (sha1(Filesystem::read($dependencyFile)) !== $dependencyFileHash) {
                    return null;
                }
            }
        }

        $data = $cacheData['data'];
        if (!is_array($data)) {
            FileSystem::delete($cacheFile);
            return null;
        }

        try {
            return LatteContextData::fromJson($data, $this->typeStringResolver);
        } catch (Exception) {
            FileSystem::delete($cacheFile);
            return null;
        }
    }
}
