<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Rule;

use Efabrica\PHPStanLatte\Analyser\FileAnalyserFactory;
use Efabrica\PHPStanLatte\Compiler\LatteToPhpCompiler;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Registry as CollectorsRegistry;
use PHPStan\Rules\Registry as RuleRegistry;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 */
final class LatteTemplatesRule implements Rule
{
    private array $latteTemplateResolvers;

    private LatteToPhpCompiler $latteToPhpCompiler;

    private FileAnalyserFactory $fileAnalyserFactory;

    private RuleRegistry $rulesRegistry;

    private CollectorsRegistry $collectorsRegistry;

    /**
     * @param LatteTemplateResolverInterface[] $latteTemplateResolvers
     */
    public function __construct(
        array $latteTemplateResolvers,
        LatteToPhpCompiler $latteToPhpCompiler,
        FileAnalyserFactory $fileAnalyserFactory,
        RuleRegistry $rulesRegistry,
        CollectorsRegistry $collectorsRegistry
    ) {
        $this->latteTemplateResolvers = $latteTemplateResolvers;
        $this->latteToPhpCompiler = $latteToPhpCompiler;
        $this->fileAnalyserFactory = $fileAnalyserFactory;
        $this->rulesRegistry = $rulesRegistry;
        $this->collectorsRegistry = $collectorsRegistry;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $workingDir = getcwd();

        $errors = [];
        foreach ($this->latteTemplateResolvers as $latteTemplateResolver) {
            if (!$latteTemplateResolver->check($node, $scope)) {
                continue;
            }

            $templates = $latteTemplateResolver->findTemplatesWithParameters($node, $scope);

            foreach ($templates as $template) {
                $templatePath = $template->getPath();
                $phpContent = $this->latteToPhpCompiler->compile(file_get_contents($templatePath), $template->getVariables());
                $templateDir = pathinfo($templatePath, PATHINFO_DIRNAME);
                $templateFileName = pathinfo($templatePath, PATHINFO_BASENAME);

                // TODO create hash

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

                foreach ($fileAnalyserResult->getErrors() as $error) {
                    // ignore all errors connected with name PHPStanLatteTemplate
                    if (str_contains($error->getMessage(), 'PHPStanLatteTemplate')) {
                        continue;
                    }
                    $errors[] = RuleErrorBuilder::message($error->getMessage())
                        ->file($templatePath)
                        ->line($error->getLine())   // TODO remap lines to latte lines
                        ->metadata(['context' => $scope->getFile()])
                        ->build();
                }
            }
        }
        return $errors;
    }
}
