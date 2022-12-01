<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Rule;

use Efabrica\PHPStanLatte\Analyser\AnalysedTemplatesRegistry;
use Efabrica\PHPStanLatte\Analyser\FileAnalyserFactory;
use Efabrica\PHPStanLatte\Collector\Finder\ResolvedNodeFinder;
use Efabrica\PHPStanLatte\Collector\IncludePathCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedIncludePath;
use Efabrica\PHPStanLatte\Compiler\LatteToPhpCompiler;
use Efabrica\PHPStanLatte\Error\ErrorBuilder;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\Template\Template;
use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Registry as CollectorsRegistry;
use PHPStan\File\RelativePathHelper;
use PHPStan\Node\CollectedDataNode;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Rules\Registry as RuleRegistry;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Throwable;

/**
 * @phpstan-import-type CollectedIncludePathArray from CollectedIncludePath
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

    private IncludePathCollector $includePathCollector;

    private ErrorBuilder $errorBuilder;

    private TypeStringResolver $typeStringResolver;

    private RelativePathHelper $relativePathHelper;

    /**
     * @param LatteTemplateResolverInterface[] $latteTemplateResolvers
     */
    public function __construct(
        array $latteTemplateResolvers,
        LatteToPhpCompiler $latteToPhpCompiler,
        FileAnalyserFactory $fileAnalyserFactory,
        AnalysedTemplatesRegistry $analysedTemplatesRegistry,
        RuleRegistry $rulesRegistry,
        IncludePathCollector $includePathCollector,
        ErrorBuilder $errorBuilder,
        TypeStringResolver $typeStringResolver,
        RelativePathHelper $relativePathHelper
    ) {
        $this->latteTemplateResolvers = $latteTemplateResolvers;
        $this->latteToPhpCompiler = $latteToPhpCompiler;
        $this->fileAnalyserFactory = $fileAnalyserFactory;
        $this->analysedTemplatesRegistry = $analysedTemplatesRegistry;
        $this->rulesRegistry = $rulesRegistry;
        $this->includePathCollector = $includePathCollector;
        $this->errorBuilder = $errorBuilder;
        $this->typeStringResolver = $typeStringResolver;
        $this->relativePathHelper = $relativePathHelper;
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
        $resolvedNodeFinder = new ResolvedNodeFinder($collectedDataNode);

        $errors = [];
        foreach ($this->latteTemplateResolvers as $latteTemplateResolver) {
            foreach ($resolvedNodeFinder->find(get_class($latteTemplateResolver)) as $collectedResolvedNode) {
                $templates = $latteTemplateResolver->findTemplates($collectedResolvedNode, $collectedDataNode);
                $this->analyseTemplates($templates, $scope, $errors);
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
                $compileFilePath = $this->latteToPhpCompiler->compileFile($actualClass, $templatePath, $template->getVariables(), $template->getComponents());
                require($compileFilePath); // load type definitions from compiled template
            } catch (Throwable $e) {
                $errors = array_merge($errors, $this->errorBuilder->buildErrors([new Error($e->getMessage(), $scope->getFile())], $templatePath, $context));
                continue;
            }

            $fileAnalyserResult = $this->fileAnalyserFactory->create()->analyseFile(
                $compileFilePath,
                [],
                $this->rulesRegistry,
                new CollectorsRegistry([$this->includePathCollector]),
                null
            );
            $this->analysedTemplatesRegistry->templateAnalysed($templatePath);

            $errors = array_merge($errors, $this->errorBuilder->buildErrors($fileAnalyserResult->getErrors(), $templatePath, $context));

            $dir = dirname($templatePath);

            $collectedDataList = $fileAnalyserResult->getCollectedData();
            $includeTemplates = [];
            foreach ($collectedDataList as $collectedData) {
                /** @phpstan-var CollectedIncludePathArray $data */
                $data = $collectedData->getData();
                $collectedIncludedPath = CollectedIncludePath::fromArray($data, $this->typeStringResolver);
                $includeTemplates[] = new Template(
                    realpath($dir . '/' . $collectedIncludedPath->getPath()) ?: '',
                    $actualClass,
                    $actualAction,
                    array_merge($collectedIncludedPath->getVariables(), $template->getVariables()),
                    $template->getComponents(),
                    $template->getPath()
                );
            }
            $this->analyseTemplates($includeTemplates, $scope, $errors, $alreadyAnalysed);
        }
    }
}
