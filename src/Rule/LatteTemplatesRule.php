<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Rule;

use Efabrica\PHPStanLatte\Analyser\AnalysedTemplatesRegistry;
use Efabrica\PHPStanLatte\Analyser\FileAnalyserFactory;
use Efabrica\PHPStanLatte\Analyser\LatteContextAnalyser;
use Efabrica\PHPStanLatte\Collector\Collector\ResolvedNodeCollector;
use Efabrica\PHPStanLatte\Collector\Finder\ResolvedNodeFinder;
use Efabrica\PHPStanLatte\Compiler\LatteToPhpCompiler;
use Efabrica\PHPStanLatte\Error\ErrorBuilder;
use Efabrica\PHPStanLatte\Helper\VariablesHelper;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\Template\Template;
use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Registry as CollectorsRegistry;
use PHPStan\File\RelativePathHelper;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Registry as RuleRegistry;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Throwable;

/**
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

    private ErrorBuilder $errorBuilder;

    private RelativePathHelper $relativePathHelper;

    private LatteContextAnalyser $latteContextAnalyser;

    /**
     * @param LatteTemplateResolverInterface[] $latteTemplateResolvers
     */
    public function __construct(
        array $latteTemplateResolvers,
        LatteToPhpCompiler $latteToPhpCompiler,
        FileAnalyserFactory $fileAnalyserFactory,
        AnalysedTemplatesRegistry $analysedTemplatesRegistry,
        RuleRegistry $rulesRegistry,
        ErrorBuilder $errorBuilder,
        RelativePathHelper $relativePathHelper,
        LatteContextAnalyser $latteContextAnalyser
    ) {
        $this->latteTemplateResolvers = $latteTemplateResolvers;
        $this->latteToPhpCompiler = $latteToPhpCompiler;
        $this->fileAnalyserFactory = $fileAnalyserFactory;
        $this->analysedTemplatesRegistry = $analysedTemplatesRegistry;
        $this->rulesRegistry = $rulesRegistry;
        $this->errorBuilder = $errorBuilder;
        $this->relativePathHelper = $relativePathHelper;
        $this->latteContextAnalyser = $latteContextAnalyser;
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
        $resolvedNodeFinder = new ResolvedNodeFinder($collectedDataNode);

        $processedFiles = array_unique(array_keys($resolvedNodes));
        $latteContext = $this->latteContextAnalyser->analyseFiles($processedFiles);

        $errors = $latteContext->getErrors();
        $templates = [];
        foreach ($this->latteTemplateResolvers as $latteTemplateResolver) {
            foreach ($resolvedNodeFinder->find(get_class($latteTemplateResolver)) as $collectedResolvedNode) {
                $result = $latteTemplateResolver->resolve($collectedResolvedNode, $latteContext);
                $errors = array_merge($errors, $result->getErrors());
                $templates = array_merge($templates, $result->getTemplates());
            }
        }

        $this->analyseTemplates($templates, $errors);

        if (count($errors) > 1000) {
            $errors[] = RuleErrorBuilder::message('Too many errors in latte.')->build();
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
    private function analyseTemplates(array $templates, array &$errors, array &$alreadyAnalysed = []): void
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
                $compileFilePath = $this->latteToPhpCompiler->compileFile($template, $context);
                require($compileFilePath); // load type definitions from compiled template
            } catch (Throwable $e) {
                $errors = array_merge($errors, $this->errorBuilder->buildErrors([new Error($e->getMessage() ?: get_class($e), $template->getPath())], $templatePath, $context));
                continue;
            }

            $fileAnalyserResult = $this->fileAnalyserFactory->create()->analyseFile(
                $compileFilePath,
                [],
                $this->rulesRegistry,
                new CollectorsRegistry([]), // TODO predavat CollectorRegistry protoze nektere rules muzou pozadovat collector aby fungovaly
                null
            );
            $this->analysedTemplatesRegistry->templateAnalysed($templatePath);

            $errors = array_merge($errors, $this->errorBuilder->buildErrors($fileAnalyserResult->getErrors(), $templatePath, $context));

            if (count($errors) > 1000) {
                return;
            }

            $dir = dirname($templatePath);

            $includeTemplates = [];
            // TODO optimization - run only template render collectors
            $collectedTemplateRenders = $this->latteContextAnalyser->analyseFile($compileFilePath)->getCollectedData(CollectedTemplateRender::class);
            foreach ($collectedTemplateRenders as $collectedTemplateRender) {
                $includedTemplatePath = $collectedTemplateRender->getTemplatePath();
                if (is_string($includedTemplatePath) && $includedTemplatePath !== '') {
                    if ($includedTemplatePath[0] !== '/') {
                        $includedTemplatePath = $dir . '/' . $includedTemplatePath;
                    }
                    $includedTemplatePath = realpath($includedTemplatePath) ?: $includedTemplatePath;
                    $includeTemplates[] = new Template(
                        $includedTemplatePath,
                        $actualClass,
                        $actualAction,
                        VariablesHelper::merge($template->getVariables(), $collectedTemplateRender->getVariables()),
                        $template->getComponents(),
                        $template->getForms(),
                        $template->getFilters(),
                        $template->getPath()
                    );
                } elseif ($includedTemplatePath === '') {
                    $errors[] = $this->errorBuilder->buildError(
                        new Error('Empty path to included latte template.', $collectedTemplateRender->getFile(), $collectedTemplateRender->getLine()),
                        $templatePath
                    );
                } elseif ($includedTemplatePath === false) {
                    $errors[] = $this->errorBuilder->buildError(
                        new Error('Cannot resolve included latte template.', $collectedTemplateRender->getFile(), $collectedTemplateRender->getLine()),
                        $templatePath
                    );
                }
            }
            $this->analyseTemplates($includeTemplates, $errors, $alreadyAnalysed);
        }
    }
}
