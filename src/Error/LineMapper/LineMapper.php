<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error\LineMapper;

use InvalidArgumentException;

final class LineMapper
{
    private bool $debugMode;

    /** @var array<string, LineMap> */
    private array $lineMaps = [];

    public function __construct(bool $debugMode = false)
    {
        $this->debugMode = $debugMode;
    }

    public function getLineMap(string $compiledTemplatePath): LineMap
    {
        if (!file_exists($compiledTemplatePath)) {
            throw new InvalidArgumentException('Compiled template file "' . $compiledTemplatePath . '" doesn\'t exist.');
        }
        if (isset($this->lineMaps[$compiledTemplatePath])) {
            return $this->lineMaps[$compiledTemplatePath];
        }
        $lineMapFile = $compiledTemplatePath . '.map';
        if ($this->debugMode || !file_exists($lineMapFile)) {
            $lineMap = $this->parseLineMap($compiledTemplatePath);
            file_put_contents($lineMapFile, json_encode($lineMap->getLines()));
        } else {
            /** @var array<int, int> $lines */
            $lines = json_decode((string)file_get_contents($lineMapFile), true);
            $lineMap = new LineMap($lines);
        }
        $this->lineMaps[$compiledTemplatePath] = $lineMap;
        return $lineMap;
    }

    private function parseLineMap(string $compiledTemplatePath): LineMap
    {
        $phpContent = file_get_contents($compiledTemplatePath) ?: '';
        $phpLineContents = explode("\n", $phpContent);

        $lineMap = new LineMap();
        foreach ($phpLineContents as $i => $phpLineContent) {
            $pattern = '/\*(.*?)line (?<number>\d+)(.*?)\*/';
            preg_match($pattern, $phpLineContent, $matches);

            $latteLine = isset($matches['number']) ? (int)$matches['number'] : null;
            if ($latteLine === null) {
                continue;
            }

            $lineMap->add($i + 1, $latteLine);
        }
        return $lineMap;
    }
}
