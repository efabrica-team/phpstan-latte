<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Rule;

use Efabrica\PHPStanLatte\Analyser\AnalysedTemplatesRegistry;
use Efabrica\PHPStanLatte\Analyser\FileAnalyserFactory;
use Efabrica\PHPStanLatte\Collector\Finder\MethodCallFinder;
use Efabrica\PHPStanLatte\Collector\Finder\ResolvedNodeFinder;
use Efabrica\PHPStanLatte\Collector\MethodCallCollector;
use Efabrica\PHPStanLatte\Collector\RelatedFilesCollector;
use Efabrica\PHPStanLatte\Collector\PHPStanLatteCollectorInterface;
use Efabrica\PHPStanLatte\Collector\ResolvedNodeCollector;
use Efabrica\PHPStanLatte\Collector\TemplateRenderCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedTemplateRender;
use Efabrica\PHPStanLatte\Compiler\LatteToPhpCompiler;
use Efabrica\PHPStanLatte\Error\ErrorBuilder;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Registry;
use PHPStan\Collectors\Registry as CollectorsRegistry;
use PHPStan\File\RelativePathHelper;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\DirectRegistry;
use PHPStan\Rules\Registry as RuleRegistry;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Throwable;

/**
 * @phpstan-import-type CollectedTemplateRenderArray from CollectedTemplateRender
 * @implements Rule<CollectedDataNode>
 */
final class LatteTemplatesRule implements Rule
{
    /** @var LatteTemplateResolverInterface[] */
    private array $latteTemplateResolvers;

    private LatteToPhpCompiler $latteToPhpCompiler;

    private FileAnalyserFactory $fileAnalyserFactory;

    private AnalysedTemplatesRegistry $analysedTemplatesRegistry;

    private RuleRegistry $rulesRegistry;

    /** @var PHPStanLatteCollectorInterface[] */
    private array $latteCollectors;

    /** @var PHPStanLatteCollectorInterface[] */
    private array $latteCollectorsForAnalyse;

    private TemplateRenderCollector $templateRenderCollector;

    private ErrorBuilder $errorBuilder;

    private RelativePathHelper $relativePathHelper;

    private TypeSerializer $typeSerializer;

    /**
     * @param LatteTemplateResolverInterface[] $latteTemplateResolvers
     * @param PHPStanLatteCollectorInterface[] $latteCollectors
     */
    public function __construct(
        array $latteTemplateResolvers,
        LatteToPhpCompiler $latteToPhpCompiler,
        FileAnalyserFactory $fileAnalyserFactory,
        AnalysedTemplatesRegistry $analysedTemplatesRegistry,
        RuleRegistry $rulesRegistry,
        array $latteCollectors,
        TemplateRenderCollector $templateRenderCollector,
        ErrorBuilder $errorBuilder,
        RelativePathHelper $relativePathHelper,
        TypeSerializer $typeSerializer
    ) {
        $this->latteTemplateResolvers = $latteTemplateResolvers;
        $this->latteToPhpCompiler = $latteToPhpCompiler;
        $this->fileAnalyserFactory = $fileAnalyserFactory;
        $this->analysedTemplatesRegistry = $analysedTemplatesRegistry;
        $this->rulesRegistry = $rulesRegistry;
        $this->latteCollectors = $latteCollectors;
        $this->latteCollectorsForAnalyse = array_filter($latteCollectors, function (PHPStanLatteCollectorInterface $latteCollector) {
            return !$latteCollector instanceof ResolvedNodeCollector;
        });
        $this->templateRenderCollector = $templateRenderCollector;
        $this->errorBuilder = $errorBuilder;
        $this->relativePathHelper = $relativePathHelper;
        $this->typeSerializer = $typeSerializer;
    }

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /**
     * @param CollectedDataNode $collectedDataNode
     */
    public function processNode(Node $collectedDataNode, Scope $scope): array
    {
        $resolvedNodes = $collectedDataNode->get(ResolvedNodeCollector::class);
        $processedFiles = array_unique(array_keys($resolvedNodes));
        $collectedDataNode = $this->collectAdditionalData($collectedDataNode, $processedFiles);
        $resolvedNodeFinder = new ResolvedNodeFinder($collectedDataNode, $this->typeSerializer);

        $errors = [];
        foreach ($this->latteTemplateResolvers as $latteTemplateResolver) {
            foreach ($resolvedNodeFinder->find(get_class($latteTemplateResolver)) as $collectedResolvedNode) {
                $result = $latteTemplateResolver->resolve($collectedResolvedNode, $collectedDataNode);
                $errors = array_merge($errors, $result->getErrors());
                $this->analyseTemplates($result->getTemplates(), $scope, $errors);
            }
        }

        foreach ($this->analysedTemplatesRegistry->getReportedUnanalysedTemplates() as $templatePath) {
            $errors[] = RuleErrorBuilder::message('Latte template ' . pathinfo($templatePath, PATHINFO_BASENAME) . ' was not analysed.')
                ->file($templatePath)
                ->build();
        }

        return $errors;
    }

    /**
     * @param string[] $newFilesToCheck
     */
    private function collectAdditionalData(CollectedDataNode $collectedDataNode, array $newFilesToCheck): CollectedDataNode
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

        $collectedParents = $newCollectedDataNode->get(RelatedFilesCollector::class);
        $processedFiles = array_unique(array_merge(array_keys($collectedParents)));
        $relatedFiles = array_unique(array_merge(...array_merge(...array_values($collectedParents))));
        $newFilesToCheck = array_diff($relatedFiles, $processedFiles);

//        print_R($newFilesToCheck);
//exit;
        return $this->collectAdditionalData($newCollectedDataNode, $newFilesToCheck);
    }

    /**
     * @return CollectedData[]
     */
    private function createCollectedDataFromNode(CollectedDataNode $collectedDataNode): array
    {
        $collectedData = [];
        foreach ($this->latteCollectors as $collector) {
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

    /**
     * @param Template[] $templates
     * @param RuleError[] $errors
     * @param array<string, int> $alreadyAnalysed
     */
    private function analyseTemplates(array $templates, Scope $scope, array &$errors, array &$alreadyAnalysed = []): void
    {
        foreach ($templates as $template) {
            $templatePath = $template->getPath();

            if (!array_key_exists($templatePath, $alreadyAnalysed)) {
                $alreadyAnalysed[$templatePath] = 1;
            } elseif ($alreadyAnalysed[$templatePath] <= 3) {
                $alreadyAnalysed[$templatePath]++;
            } else {
                continue; // stop recursion when template is analysed more than 3 times in include chain
            }

            $context = '';
            $actualClass = $template->getActualClass();
            if ($actualClass !== null) {
                $context .= $actualClass;
            }
            $actualAction = $template->getActualAction();
            if ($actualAction !== null) {
                $context .= '::' . $actualAction;
            }
            $parentTemplatePath = $template->getParentTemplatePath();
            if ($parentTemplatePath !== null) {
                $context .= ' included in ' . $this->relativePathHelper->getRelativePath($parentTemplatePath);
            }

            try {
                $compileFilePath = $this->latteToPhpCompiler->compileFile($actualClass, $templatePath, $template->getVariables(), $template->getComponents(), $template->getForms(), $context);
                require($compileFilePath); // load type definitions from compiled template
            } catch (Throwable $e) {
                $errors = array_merge($errors, $this->errorBuilder->buildErrors([new Error($e->getMessage() ?: get_class($e), $scope->getFile())], $templatePath, $context));
                continue;
            }

            $fileAnalyserResult = $this->fileAnalyserFactory->create()->analyseFile(
                $compileFilePath,
                [],
                $this->rulesRegistry,
                new CollectorsRegistry([$this->templateRenderCollector]),
                null
            );
            $this->analysedTemplatesRegistry->templateAnalysed($templatePath);

            $errors = array_merge($errors, $this->errorBuilder->buildErrors($fileAnalyserResult->getErrors(), $templatePath, $context));

            $dir = dirname($templatePath);

            $includeTemplates = [];
            $collectedTemplateRenders = $this->templateRenderCollector->extractCollectedData($fileAnalyserResult->getCollectedData(), $this->typeSerializer, CollectedTemplateRender::class);
            foreach ($collectedTemplateRenders as $collectedTemplateRender) {
                $includedTemplatePath = $collectedTemplateRender->getTemplatePath();
                if (is_string($includedTemplatePath)) {
                    $includeTemplates[] = new Template(
                        realpath($dir . '/' . $collectedTemplateRender->getTemplatePath()) ?: '',
                        $actualClass,
                        $actualAction,
                        array_merge($collectedTemplateRender->getVariables(), $template->getVariables()),
                        $template->getComponents(),
                        $template->getForms(),
                        $template->getPath()
                    );
                } elseif ($includedTemplatePath === false) {
                    $errors[] = $this->errorBuilder->buildError(
                        new Error('Cannot resolve included latte template.', $collectedTemplateRender->getFile(), $collectedTemplateRender->getLine()),
                        $templatePath
                    );
                }
            }
            $this->analyseTemplates($includeTemplates, $scope, $errors, $alreadyAnalysed);
        }
    }
}
