<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error\LineMapper;

use InvalidArgumentException;
use PhpParser\NodeTraverser;
use PHPStan\Parser\Parser;

final class LineMapper
{
    private Parser $parser;

    private bool $debugMode;

    /** @var array<string, LineMap> */
    private array $lineMaps = [];

    /**
     * @param Parser $parser
     */
    public function __construct(Parser $parser, bool $debugMode = false)
    {
        $this->parser = $parser;
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
        $phpStmts = $this->parser->parseFile($compiledTemplatePath);
        $lineMap = new LineMap();
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new LineNumberNodeVisitor($lineMap));
        $nodeTraverser->traverse($phpStmts);
        return $lineMap;
    }
}
