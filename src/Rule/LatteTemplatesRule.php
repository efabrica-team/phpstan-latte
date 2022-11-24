<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Rule;

use Efabrica\PHPStanLatte\Analyser\FileAnalyserFactory;
use Efabrica\PHPStanLatte\Collector\Finder\ResolvedClassFinder;
use Efabrica\PHPStanLatte\Compiler\LatteToPhpCompiler;
use Efabrica\PHPStanLatte\Error\ErrorBuilder;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use Latte\CompileException;
use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Registry as CollectorsRegistry;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Registry as RuleRegistry;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<CollectedDataNode>
 */
final class LatteTemplatesRule implements Rule
{
    /** @var LatteTemplateResolverInterface[] */
    private array $latteTemplateResolvers;

    private LatteToPhpCompiler $latteToPhpCompiler;

    private FileAnalyserFactory $fileAnalyserFactory;

    private RuleRegistry $rulesRegistry;

    private CollectorsRegistry $collectorsRegistry;

    private ErrorBuilder $errorBuilder;

    /**
     * @param LatteTemplateResolverInterface[] $latteTemplateResolvers
     */
    public function __construct(
        array $latteTemplateResolvers,
        LatteToPhpCompiler $latteToPhpCompiler,
        FileAnalyserFactory $fileAnalyserFactory,
        RuleRegistry $rulesRegistry,
        CollectorsRegistry $collectorsRegistry,
        ErrorBuilder $errorBuilder
    ) {
        $this->latteTemplateResolvers = $latteTemplateResolvers;
        $this->latteToPhpCompiler = $latteToPhpCompiler;
        $this->fileAnalyserFactory = $fileAnalyserFactory;
        $this->rulesRegistry = $rulesRegistry;
        $this->collectorsRegistry = $collectorsRegistry;
        $this->errorBuilder = $errorBuilder;
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
        $resolvedClassFinder = new ResolvedClassFinder($collectedDataNode);

        $errors = [];
        foreach ($this->latteTemplateResolvers as $latteTemplateResolver) {
            foreach ($resolvedClassFinder->find(get_class($latteTemplateResolver)) as $className) {
                $templates = $latteTemplateResolver->findTemplates($className, $collectedDataNode);
                foreach ($templates as $template) {
                    $templatePath = $template->getPath();

                    try {
                        $compileFilePath = $this->latteToPhpCompiler->compileFile($className, $templatePath, $template->getVariables(), $template->getComponents());
                    } catch (CompileException $e) { // TODO change to PHPStanLatteCompilerExceptioin
                        $errors = array_merge($errors, $this->errorBuilder->buildErrors([new Error($e->getMessage(), $scope->getFile())], $templatePath, $scope));
                        continue;
                    }

                    $fileAnalyserResult = $this->fileAnalyserFactory->create()->analyseFile(
                        $compileFilePath,
                        [],
                        $this->rulesRegistry,
                        $this->collectorsRegistry,
                        null
                    );

                    $errors = array_merge($errors, $this->errorBuilder->buildErrors($fileAnalyserResult->getErrors(), $templatePath, $scope));
                }
            }
        }
        return $errors;
    }
}
