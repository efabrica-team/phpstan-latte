<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Rule;

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
use PHPStan\Node\CollectedDataNode;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Rules\Registry as RuleRegistry;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
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

    private RuleRegistry $rulesRegistry;

    private IncludePathCollector $includePathCollector;

    private ErrorBuilder $errorBuilder;

    private TypeStringResolver $typeStringResolver;

    /**
     * @param LatteTemplateResolverInterface[] $latteTemplateResolvers
     */
    public function __construct(
        array $latteTemplateResolvers,
        LatteToPhpCompiler $latteToPhpCompiler,
        FileAnalyserFactory $fileAnalyserFactory,
        RuleRegistry $rulesRegistry,
        IncludePathCollector $includePathCollector,
        ErrorBuilder $errorBuilder,
        TypeStringResolver $typeStringResolver
    ) {
        $this->latteTemplateResolvers = $latteTemplateResolvers;
        $this->latteToPhpCompiler = $latteToPhpCompiler;
        $this->fileAnalyserFactory = $fileAnalyserFactory;
        $this->rulesRegistry = $rulesRegistry;
        $this->includePathCollector = $includePathCollector;
        $this->errorBuilder = $errorBuilder;
        $this->typeStringResolver = $typeStringResolver;
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
        return $errors;
    }

    /**
     * @param Template[] $templates
     * @param RuleError[] $errors
     */
    private function analyseTemplates(array $templates, Scope $scope, array &$errors): void
    {
        foreach ($templates as $template) {
            $templatePath = $template->getPath();

            try {
                $compileFilePath = $this->latteToPhpCompiler->compileFile($template->getActualClass(), $templatePath, $template->getVariables(), $template->getComponents());
            } catch (Throwable $e) {
                $errors = array_merge($errors, $this->errorBuilder->buildErrors([new Error($e->getMessage(), $scope->getFile())], $templatePath, $scope));
                continue;
            }

            $fileAnalyserResult = $this->fileAnalyserFactory->create()->analyseFile(
                $compileFilePath,
                [],
                $this->rulesRegistry,
                new CollectorsRegistry([$this->includePathCollector]),
                null
            );

            $errors = array_merge($errors, $this->errorBuilder->buildErrors($fileAnalyserResult->getErrors(), $templatePath, $scope));

            $dir = dirname($templatePath);

            $collectedDataList = $fileAnalyserResult->getCollectedData();
            $includeTemplates = [];
            foreach ($collectedDataList as $collectedData) {
                /** @phpstan-var CollectedIncludePathArray $data */
                $data = $collectedData->getData();
                $collectedIncludedPath = CollectedIncludePath::fromArray($data, $this->typeStringResolver);
                $includeTemplates[] = new Template($dir . '/' . $collectedIncludedPath->getPath(), $template->getActualClass(), array_merge($collectedIncludedPath->getVariables(), $template->getVariables()), $template->getComponents());
            }
            $this->analyseTemplates($includeTemplates, $scope, $errors);
        }
    }
}
