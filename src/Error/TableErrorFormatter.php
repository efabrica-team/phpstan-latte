<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalyseCommand;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\CiDetectedErrorFormatter;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use PHPStan\File\SimpleRelativePathHelper;

final class TableErrorFormatter implements ErrorFormatter
{
    private RelativePathHelper $relativePathHelper;

    private SimpleRelativePathHelper $simpleRelativePathHelper;

    private CiDetectedErrorFormatter $ciDetectedErrorFormatter;

    private bool $showTipsOfTheDay;

    private ?string $editorUrl;

    public function __construct(
        RelativePathHelper $relativePathHelper,
        SimpleRelativePathHelper $simpleRelativePathHelper,
        CiDetectedErrorFormatter $ciDetectedErrorFormatter,
        bool $showTipsOfTheDay,
        ?string $editorUrl
    ) {
        $this->relativePathHelper = $relativePathHelper;
        $this->simpleRelativePathHelper = $simpleRelativePathHelper;
        $this->ciDetectedErrorFormatter = $ciDetectedErrorFormatter;
        $this->showTipsOfTheDay = $showTipsOfTheDay;
        $this->editorUrl = $editorUrl;
    }

    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $this->ciDetectedErrorFormatter->formatErrors($analysisResult, $output);
        $projectConfigFile = 'phpstan.neon';
        if ($analysisResult->getProjectConfigFile() !== null) {
            $projectConfigFile = $this->relativePathHelper->getRelativePath($analysisResult->getProjectConfigFile());
        }
        $style = $output->getStyle();
        if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
            $style->success('No errors');
            if ($this->showTipsOfTheDay) {
                if ($analysisResult->isDefaultLevelUsed()) {
                    $output->writeLineFormatted('💡 Tip of the Day:');
                    $output->writeLineFormatted(sprintf("PHPStan is performing only the most basic checks.\nYou can pass a higher rule level through the <fg=cyan>--%s</> option\n(the default and current level is %d) to analyse code more thoroughly.", AnalyseCommand::OPTION_LEVEL, AnalyseCommand::DEFAULT_LEVEL));
                    $output->writeLineFormatted('');
                }
            }
            return 0;
        }

        /** @var array<string, Error[]> $fileErrors */
        $fileErrors = [];
        foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
            $key = $this->relativePathHelper->getRelativePath($fileSpecificError->getFile());
            /** @var string|null $context */
            $context = $fileSpecificError->getMetadata()['context'] ?? null;
            if ($context !== null) {
                $key .= ' rendered from ' . $context;
            }
            if (!isset($fileErrors[$key])) {
                $fileErrors[$key] = [];
            }
            $fileErrors[$key][] = $fileSpecificError;
        }

        foreach ($fileErrors as $key => $errors) {
            $rows = [];
            foreach ($errors as $error) {
                $message = $error->getMessage();
                if ($error->getTip() !== null) {
                    $tip = $error->getTip();
                    $tip = str_replace('%configurationFile%', $projectConfigFile, $tip);
                    $message .= "\n💡 " . $tip;
                }
                if (is_string($this->editorUrl)) {
                    $editorFile = $error->getTraitFilePath() ?? $error->getFilePath();
                    $url = str_replace(['%file%', '%relFile%', '%line%'], [$editorFile, $this->simpleRelativePathHelper->getRelativePath($editorFile), (string) $error->getLine()], $this->editorUrl);
                    $message .= "\n✏️  <href=" . $url . '>' . $this->relativePathHelper->getRelativePath($editorFile) . '</>';
                }
                $rows[] = [$this->formatLineNumber($error->getLine()), $message];
            }
            $style->table(['Line', $key ], $rows);
        }

        if (count($analysisResult->getNotFileSpecificErrors()) > 0) {
            $style->table(['', 'Error'], array_map(static function (string $error): array {
                return ['', $error];
            }, $analysisResult->getNotFileSpecificErrors()));
        }

        $warningsCount = count($analysisResult->getWarnings());
        if ($warningsCount > 0) {
            $style->table(['', 'Warning'], array_map(static function (string $warning): array {
                return ['', $warning];
            }, $analysisResult->getWarnings()));
        }

        $finalMessage = sprintf($analysisResult->getTotalErrorsCount() === 1 ? 'Found %d error' : 'Found %d errors', $analysisResult->getTotalErrorsCount());
        if ($warningsCount > 0) {
            $finalMessage .= sprintf($warningsCount === 1 ? ' and %d warning' : ' and %d warnings', $warningsCount);
        }

        if ($analysisResult->getTotalErrorsCount() > 0) {
            $style->error($finalMessage);
        } else {
            $style->warning($finalMessage);
        }
        return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
    }

    private function formatLineNumber(?int $lineNumber): string
    {
        if ($lineNumber === null) {
            return '';
        }
        $isRunningInVSCodeTerminal = getenv('TERM_PROGRAM') === 'vscode';
        if ($isRunningInVSCodeTerminal) {
            return ':' . $lineNumber;
        }
        return (string) $lineNumber;
    }
}
