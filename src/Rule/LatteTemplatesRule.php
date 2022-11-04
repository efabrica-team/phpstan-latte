<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Rule;

use Efabrica\PHPStanLatte\Analyser\FileAnalyserFactory;
use Efabrica\PHPStanLatte\Compiler\LatteToPhpCompiler;
use Efabrica\PHPStanLatte\Compiler\LineMapper;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use Latte\CompileException;
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
    /** @var LatteTemplateResolverInterface[] */
    private array $latteTemplateResolvers;

    private LatteToPhpCompiler $latteToPhpCompiler;

    private FileAnalyserFactory $fileAnalyserFactory;

    private RuleRegistry $rulesRegistry;

    private CollectorsRegistry $collectorsRegistry;
    private LineMapper $lineMapper;

    /**
     * @param LatteTemplateResolverInterface[] $latteTemplateResolvers
     */
    public function __construct(
        array $latteTemplateResolvers,
        LatteToPhpCompiler $latteToPhpCompiler,
        FileAnalyserFactory $fileAnalyserFactory,
        RuleRegistry $rulesRegistry,
        CollectorsRegistry $collectorsRegistry,

        LineMapper $lineMapper
    ) {
        $this->latteTemplateResolvers = $latteTemplateResolvers;
        $this->latteToPhpCompiler = $latteToPhpCompiler;
        $this->fileAnalyserFactory = $fileAnalyserFactory;
        $this->rulesRegistry = $rulesRegistry;
        $this->collectorsRegistry = $collectorsRegistry;
        $this->lineMapper = $lineMapper;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $workingDir = getcwd() ?: '';

        $errors = [];
        foreach ($this->latteTemplateResolvers as $latteTemplateResolver) {
            if (!$latteTemplateResolver->check($node, $scope)) {
                continue;
            }

            $templates = $latteTemplateResolver->findTemplatesWithParameters($node, $scope);

            foreach ($templates as $template) {
                $templatePath = $template->getPath();

                try {
                    $phpContent = $this->latteToPhpCompiler->compile(file_get_contents($templatePath) ?: '', $template->getVariables());
                } catch (CompileException $e) {
                    $errors[] = RuleErrorBuilder::message($e->getMessage())
                        ->file($templatePath)
                        ->metadata(['context' => $scope->getFile()])
                        ->build();
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

                // TODO move to separate service

                foreach ($fileAnalyserResult->getErrors() as $error) {
                    // ignore all errors connected with name PHPStanLatteTemplate
                    if (str_contains($error->getMessage(), 'PHPStanLatteTemplate')) {
                        continue;
                    }
                    $errors[] = RuleErrorBuilder::message($error->getMessage()) // TODO remap messages not registered filters etc.
                        ->file($templatePath)
                        ->line($this->lineMapper->get((int)$error->getLine()))  // TODO remap lines to latte lines
                        ->metadata(['context' => $scope->getFile()])
                        ->build();
                }
                $this->lineMapper->reset();
            }
        }
        return $errors;
    }
}
