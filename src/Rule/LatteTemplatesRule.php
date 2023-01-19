<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Rule;

use Efabrica\PHPStanLatte\Analyser\AnalysedTemplatesRegistry;
use Efabrica\PHPStanLatte\Analyser\FileAnalyserFactory;
use Efabrica\PHPStanLatte\Analyser\LatteContextAnalyser;
use Efabrica\PHPStanLatte\Collector\Finder\ResolvedNodeFinder;
use Efabrica\PHPStanLatte\Compiler\LatteToPhpCompiler;
use Efabrica\PHPStanLatte\Error\ErrorBuilder;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\LatteContext\Collector\TemplateRenderCollector;
use Efabrica\PHPStanLatte\LatteContext\LatteContextFactory;
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

    private LatteContextAnalyser $latteIncludeAnalyser;

    private LatteContextFactory $latteContextFactory;

    /**
     * @param LatteTemplateResolverInterface[] $latteTemplateResolvers
     * @param TemplateRenderCollector[] $templateRenderCollectors
     */
    public function __construct(
        array $latteTemplateResolvers,
        LatteToPhpCompiler $latteToPhpCompiler,
        FileAnalyserFactory $fileAnalyserFactory,
        AnalysedTemplatesRegistry $analysedTemplatesRegistry,
        RuleRegistry $rulesRegistry,
        ErrorBuilder $errorBuilder,
        RelativePathHelper $relativePathHelper,
        LatteContextAnalyser $latteContextAnalyser,
        LatteContextFactory $latteContextFactory,
        array $templateRenderCollectors
    ) {
        $this->latteTemplateResolvers = $latteTemplateResolvers;
        $this->latteToPhpCompiler = $latteToPhpCompiler;
        $this->fileAnalyserFactory = $fileAnalyserFactory;
        $this->analysedTemplatesRegistry = $analysedTemplatesRegistry;
        $this->rulesRegistry = $rulesRegistry;
        $this->errorBuilder = $errorBuilder;
        $this->relativePathHelper = $relativePathHelper;
        $this->latteContextAnalyser = $latteContextAnalyser;
        $this->latteIncludeAnalyser = $latteContextAnalyser->withCollectors($templateRenderCollectors);
        $this->latteContextFactory = $latteContextFactory;
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
        $resolvedNodeFinder = new ResolvedNodeFinder($collectedDataNode, $this->latteTemplateResolvers);
        $latteContextData = $this->latteContextAnalyser->analyseFiles($resolvedNodeFinder->getAnalysedFiles());
        $latteContext = $this->latteContextFactory->create($latteContextData);

        $errors = $latteContextData->getErrors();
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
            foreach ($template->getParentTemplatePaths() as $parentTemplate) {
                $context .= ' included in ' . $this->relativePathHelper->getRelativePath(realpath($parentTemplate) ?: '');
            }

            try {
                $compileFilePath = $this->latteToPhpCompiler->compileFile($template, $context);
                require($compileFilePath); // load type definitions from compiled template
            } catch (Throwable $e) {
                $errors = array_merge($errors, $this->errorBuilder->buildErrors([new Error($e->getMessage() ?: get_class($e), $template->getPath())], $templatePath, null, $context));
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

            $errors = array_merge($errors, $this->errorBuilder->buildErrors($fileAnalyserResult->getErrors(), $templatePath, $compileFilePath, $context));

            if (count($errors) > 1000) {
                return;
            }

            $dir = dirname($templatePath);

            $includeTemplates = [];
            $collectedTemplateRenders = $this->latteIncludeAnalyser->analyseFile($compileFilePath)->getCollectedData(CollectedTemplateRender::class);
            foreach ($collectedTemplateRenders as $collectedTemplateRender) {
                $includedTemplatePath = $collectedTemplateRender->getTemplatePath();
                if (is_string($includedTemplatePath) && $includedTemplatePath !== '') {
                    if ($includedTemplatePath[0] !== '/') {
                        $includedTemplatePath = $dir . '/' . $includedTemplatePath;
                    }
                    if (!is_file($includedTemplatePath)) {
                        $errors[] = $this->errorBuilder->buildError(
                            new Error('Included latte template ' . $includedTemplatePath . ' does not exist.', $collectedTemplateRender->getFile(), $collectedTemplateRender->getLine()),
                            $templatePath,
                            $compileFilePath
                        );
                    } else {
                        $includedTemplatePath = realpath($includedTemplatePath) ?: $includedTemplatePath;
                        $includeTemplate = new Template(
                            $includedTemplatePath,
                            $actualClass,
                            $actualAction,
                            $template->getTemplateContext()->mergeVariables($collectedTemplateRender->getVariables()),
                            array_merge([$template->getPath()], $template->getParentTemplatePaths())
                        );
                        $includeTemplates[$includeTemplate->getSignatureHash()] = $includeTemplate;
                    }
                } elseif ($includedTemplatePath === '') {
                    $errors[] = $this->errorBuilder->buildError(
                        new Error('Empty path to included latte template.', $collectedTemplateRender->getFile(), $collectedTemplateRender->getLine()),
                        $templatePath,
                        $compileFilePath
                    );
                } elseif ($includedTemplatePath === false) {
                    $errors[] = $this->errorBuilder->buildError(
                        new Error('Cannot resolve included latte template.', $collectedTemplateRender->getFile(), $collectedTemplateRender->getLine()),
                        $templatePath,
                        $compileFilePath
                    );
                }
            }
            $this->analyseTemplates($includeTemplates, $errors, $alreadyAnalysed);
        }
    }
}
