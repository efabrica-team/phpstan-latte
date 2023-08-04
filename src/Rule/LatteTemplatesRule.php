<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Rule;

use Efabrica\PHPStanLatte\Analyser\AnalysedTemplatesRegistry;
use Efabrica\PHPStanLatte\Analyser\FileAnalyserFactory;
use Efabrica\PHPStanLatte\Analyser\LatteContextAnalyser;
use Efabrica\PHPStanLatte\Collector\Finder\ResolvedNodeFinder;
use Efabrica\PHPStanLatte\Compiler\Helper\TemplateContextHelper;
use Efabrica\PHPStanLatte\Compiler\LatteToPhpCompiler;
use Efabrica\PHPStanLatte\Error\ErrorBuilder;
use Efabrica\PHPStanLatte\Exception\ParseException;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\LatteContext\Collector\TemplateRenderCollector;
use Efabrica\PHPStanLatte\LatteContext\LatteContextFactory;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\Template\Template;
use Latte\CompileException;
use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Registry as CollectorsRegistry;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Registry as RuleRegistry;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
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

    private LatteContextAnalyser $latteContextAnalyser;

    private LatteContextAnalyser $latteIncludeAnalyser;

    private LatteContextFactory $latteContextFactory;

    private TemplateContextHelper $templateContextHelper;

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
        LatteContextAnalyser $latteContextAnalyser,
        LatteContextFactory $latteContextFactory,
        array $templateRenderCollectors,
        TemplateContextHelper $templateContextHelper
    ) {
        $this->latteTemplateResolvers = $latteTemplateResolvers;
        $this->latteToPhpCompiler = $latteToPhpCompiler;
        $this->fileAnalyserFactory = $fileAnalyserFactory;
        $this->analysedTemplatesRegistry = $analysedTemplatesRegistry;
        $this->rulesRegistry = $rulesRegistry;
        $this->errorBuilder = $errorBuilder;
        $this->latteContextAnalyser = $latteContextAnalyser;
        $this->latteIncludeAnalyser = $latteContextAnalyser->withCollectors($templateRenderCollectors);
        $this->latteContextFactory = $latteContextFactory;
        $this->templateContextHelper = $templateContextHelper;
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

        $analysedFiles = $resolvedNodeFinder->getAnalysedFiles();
        $latteContextData = $this->latteContextAnalyser->analyseFiles($analysedFiles);
        $latteContext = $this->latteContextFactory->create($latteContextData);

        $errors = array_merge($latteContextData->getErrors(), $latteContextData->getCollectedErrors());
        $templates = [];
        foreach ($this->latteTemplateResolvers as $latteTemplateResolver) {
            foreach ($resolvedNodeFinder->find(get_class($latteTemplateResolver)) as $collectedResolvedNode) {
                $result = $latteTemplateResolver->resolve($collectedResolvedNode, $latteContext);
                $errors = array_merge($errors, $result->getErrors());
                $templates = array_merge($templates, $result->getTemplates());
            }
        }

        $compiledTemplates = $this->compileTemplates($templates, $errors);
        $errors = array_merge($errors, $this->analyseTemplates($compiledTemplates));

        if (count($errors) > 1000) {
            $errors[] = RuleErrorBuilder::message('Too many errors in latte.')->build();
        }

        foreach ($this->analysedTemplatesRegistry->getReportedUnanalysedTemplates() as $templatePath) {
            $errors[] = RuleErrorBuilder::message('Latte template ' . pathinfo($templatePath, PATHINFO_BASENAME) . ' was not analysed.')
                ->file($templatePath)
                ->tip('Please make sure your template path is correct. If you use some non-standard way of resolving your templates, read our extension guide https://github.com/efabrica-team/phpstan-latte/blob/main/docs/extension.md#template-resolvers')
                ->build();
        }

        return $this->errorBuilder->buildRuleErrors($errors);
    }

    /**
     * @param Template[] $templates
     * @param RuleError[] $errors
     * @param array<string, int> $alreadyAnalysed
     * @return array<string, Template> path of compiled template => Template
     * @throws ShouldNotHappenException
     */
    private function compileTemplates(array $templates, array &$errors, array &$alreadyAnalysed = []): array
    {
        $compiledTemplates = [];
        foreach ($templates as $template) {
            $templatePath = $template->getPath();

            if ($this->analysedTemplatesRegistry->isExcludedFromAnalysing($templatePath)) {
                continue;
            }

            if (!array_key_exists($templatePath, $alreadyAnalysed)) {
                $alreadyAnalysed[$templatePath] = 1;
            } elseif ($alreadyAnalysed[$templatePath] <= 3) {
                $alreadyAnalysed[$templatePath]++;
            } else {
                continue; // stop recursion when template is analysed more than 3 times in include chain
            }

            $context = $this->templateContextHelper->getContext($template);

            try {
                $compileFilePath = $this->latteToPhpCompiler->compileFile($template, $context);
                $compiledTemplates[$compileFilePath] = $template;
            } catch (CompileException $e) {
                $ruleErrorBuilder = RuleErrorBuilder::message($e->getMessage())
                    ->file($template->getPath())
                    ->metadata(['context' => $context]);
                if ($e->sourceLine) {
                    $ruleErrorBuilder->line($e->sourceLine);
                }
                $errors[] = $ruleErrorBuilder->build();
                continue;
            } catch (ParseException $e) {
                $errors = array_merge($errors, $this->errorBuilder->buildErrors([new Error($e->getMessage(), $template->getPath(), $e->getSourceLine())], $templatePath, $e->getCompileFilePath(), $context));
                continue;
            } catch (Throwable $e) {
                $errors = array_merge($errors, $this->errorBuilder->buildErrors([new Error($e->getMessage() ?: get_class($e), $template->getPath())], $templatePath, null, $context));
                continue;
            }
            $this->analysedTemplatesRegistry->templateAnalysed($templatePath);

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
                            $template->getActualClass(),
                            $template->getActualAction(),
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
                } elseif ($includedTemplatePath === null) {
                    $errors[] = $this->errorBuilder->buildError(
                        new Error('Cannot resolve included latte template.', $collectedTemplateRender->getFile(), $collectedTemplateRender->getLine()),
                        $templatePath,
                        $compileFilePath
                    );
                }
            }

            if ($includeTemplates !== []) {
                $compiledTemplates = array_merge($compiledTemplates, $this->compileTemplates(array_values($includeTemplates), $errors, $alreadyAnalysed));
            }
        }
        return $compiledTemplates;
    }

    /**
     * @param array<string, Template> $templates
     * @return RuleError[]
     */
    private function analyseTemplates(array $templates): array
    {
        $errors = [];
        foreach ($templates as $compileFilePath => $template) {
            $fileAnalyserResult = $this->fileAnalyserFactory->create()->analyseFile(
                $compileFilePath,
                [],
                $this->rulesRegistry,
                new CollectorsRegistry([]),
                null
            );

            $errors = array_merge($errors, $this->errorBuilder->buildErrors($fileAnalyserResult->getErrors(), $template->getPath(), $compileFilePath, $this->templateContextHelper->getContext($template)));

            if (count($errors) > 1000) {
                return $errors;
            }
        }
        return $errors;
    }
}
