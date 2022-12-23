<?php

namespace Efabrica\PHPStanLatte\Analyser;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedRelatedFiles;
use Efabrica\PHPStanLatte\LatteContext\Collector\AbstractLatteContextCollector;
use PhpParser\Node;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\File\FileHelper;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider;
use Throwable;

class LatteContextAnalyser
{
    private ScopeFactory $scopeFactory;

    private NodeScopeResolver $nodeScopeResolver;

    private Parser $parser;

    private ReflectionProvider $reflectionProvider;

    private FileHelper $fileHelper;

    /** @var AbstractLatteContextCollector[] */
    private array $collectors;

    /**
     * @param AbstractLatteContextCollector[] $collectors
     */
    public function __construct(
        ScopeFactory $scopeFactory,
        NodeScopeResolver $nodeScopeResolver,
        ReflectionProvider $reflectionProvider,
        FileHelper $fileHelper,
        Parser $parser,
        array $collectors
    ) {
        $this->scopeFactory = $scopeFactory;
        $this->nodeScopeResolver = clone $nodeScopeResolver;
        $this->reflectionProvider = $reflectionProvider;
        $this->fileHelper = $fileHelper;
        // $this->nodeScopeResolver->setAnalysedFiles(null); TODO when changes in PHPStan are merged
        $this->parser = $parser;
        $this->collectors = $collectors;
    }

    /**
     * @param string[] $files
     */
    public function analyseFiles(array $files): LatteContextData
    {
        $errors = [];
        $collectedData = [];

        $this->nodeScopeResolver->setAnalysedFiles($files); // TODO when changes in PHPStan are merged

        $collectedRelatedFiles = [];
        do {
            foreach ($files as $file) {
                $fileResult = $this->analyseFile($file);
                if ($fileResult->getErrors() !== []) {
                    $errors = array_merge($errors, $fileResult->getErrors());
                }
                if ($fileResult->getAllCollectedData() !== []) {
                    $collectedData = array_merge($collectedData, $fileResult->getAllCollectedData());
                }
                $collectedRelatedFiles = array_merge($collectedRelatedFiles, $fileResult->getCollectedData(CollectedRelatedFiles::class));
            }

            $processedFiles = [];
            $relatedFiles = [];
            foreach ($collectedRelatedFiles as $collectedRelatedFile) {
                $processedFiles[] = $collectedRelatedFile->getProcessedFile();
                $relatedFiles[] = $collectedRelatedFile->getRelatedFiles();
            }
            $files = array_diff(array_unique(array_merge(...$relatedFiles)), array_unique($processedFiles));
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
                    foreach ($this->collectors as $collector) {
                        if (!is_a($node, $collector->getNodeType())) {
                            continue;
                        }
                        try {
                            $collectedData = $collector->collectData($node, $scope);
                        } catch (Throwable $e) {
                            $fileErrors[] = new Error(get_class($collector) . ' error: ' . $e->getMessage(), $file, $node->getLine(), $e);
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
                $fileErrors[] = new Error('LatteContextAnalyser error: ' . $e->getMessage(), $file, null, $e);
            }
        } elseif (is_dir($file)) {
            $fileErrors[] = new Error(sprintf('File %s is a directory.', $file), $file, null, false);
        } else {
            $fileErrors[] = new Error(sprintf('File %s does not exist.', $file), $file, null, false);
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
}
