<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Analyser;

use Nette\Utils\Finder;

final class AnalysedTemplatesRegistry
{
    /** @var string[] */
    private array $analysedPaths;

    private bool $reportUnanalysedTemplates;

    /** @var array<string, bool>  */
    private array $templateFiles;

    /**
     * @param string[] $analysedPaths
     */
    public function __construct(array $analysedPaths, bool $reportUnanalysedTemplates)
    {
        $this->analysedPaths = $analysedPaths;
        $this->reportUnanalysedTemplates = $reportUnanalysedTemplates;
        foreach ($this->getExistingTemplates() as $file) {
            $this->templateFiles[$file] = false;
        }
    }

    public function templateAnalysed(string $path): void
    {
        $this->templateFiles[$path] = true;
    }

    /**
     * @return string[]
     */
    public function getExistingTemplates(): array
    {
        $files = [];
        foreach ($this->analysedPaths as $analysedPath) {
            /** @var string $file */
            foreach (Finder::findFiles('*.latte')->from($analysedPath) as $file) {
                $files[] = (string)$file;
            }
        }
        $files = array_unique($files);
        sort($files);
        return $files;
    }

    /**
     * @return string[]
     */
    public function getAnalysedTemplates(): array
    {
        return array_keys(array_filter($this->templateFiles, function (bool $val) {
            return $val;
        }));
    }

    /**
     * @return string[]
     */
    public function getUnanalysedTemplates(): array
    {
        return array_keys(array_filter($this->templateFiles, function (bool $val) {
            return !$val;
        }));
    }

    /**
     * @return string[]
     */
    public function getReportedUnanalysedTemplates(): array
    {
        if ($this->reportUnanalysedTemplates) {
            return $this->getUnanalysedTemplates();
        } else {
            return [];
        }
    }
}
