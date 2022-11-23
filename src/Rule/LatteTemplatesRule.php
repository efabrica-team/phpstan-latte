<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Rule;

use Efabrica\PHPStanLatte\Analyser\FileAnalyserFactory;
use Efabrica\PHPStanLatte\Collector\AssignVariableToTemplateCollector;
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
 * @implements Rule<Node>
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
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $xxx = $this->collectorsRegistry->getCollectors(AssignVariableToTemplateCollector::class);
        print_R($xxx);
//        exit;

        $workingDir = getcwd() ?: '';

        $errors = [];
        foreach ($this->latteTemplateResolvers as $latteTemplateResolver) {
            if (!$latteTemplateResolver->check($node, $scope)) {
                continue;
            }

            $templates = $latteTemplateResolver->findTemplates($node, $scope);
            foreach ($templates as $template) {
                $templatePath = $template->getPath();

                try {
                    $phpContent = $this->latteToPhpCompiler->compile($scope, file_get_contents($templatePath) ?: '', $template->getVariables(), $template->getComponents());
                } catch (CompileException $e) { // TODO change to PHPStanLatteCompilerExceptioin
                    $errors = array_merge($errors, $this->errorBuilder->buildErrors([new Error($e->getMessage(), $scope->getFile())], $templatePath, $scope));
                    continue;
                }
                $templateDir = pathinfo($templatePath, PATHINFO_DIRNAME);
                $templateFileName = pathinfo($templatePath, PATHINFO_BASENAME);

                // TODO create hash from $template - variables and components can be different for the same file in different context

                // $compileDir = '/tmp/phpstan-latte/' . str_replace($workingDir, '', $templateDir);

                $compileDir = $workingDir . '/tmp/phpstan-latte/' . str_replace($workingDir, '', $templateDir);

                if (!file_exists($compileDir)) {
                    mkdir($compileDir, 0777, true);
                }
                $compileFilePath = $compileDir . '/' . $templateFileName . '.php';
                file_put_contents($compileFilePath, $phpContent);

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
        return $errors;
    }
}
