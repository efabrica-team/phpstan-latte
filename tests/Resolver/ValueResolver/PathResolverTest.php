<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Resolver\ValueResolver;

use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPStan\Testing\PHPStanTestCase;
use Symfony\Component\Finder\Finder;

final class PathResolverTest extends PHPStanTestCase
{
    private PathResolver $pathResolver;

    private Parser $phpParser;

    public function setUp(): void
    {
        $this->pathResolver = new PathResolver(true, new ValueResolver());
        $parserFactory = new ParserFactory();
        $this->phpParser = $parserFactory->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * @dataProvider fixtures
     */
    public function testResolve(string $path): void
    {
        [$php, $output] = array_map('trim', explode('-----', file_get_contents($path)));
        $output = str_replace('{$dir}', dirname($path), $output);
        if ($output === 'null') {
            $output = null;
        }

        $stmts = $this->phpParser->parse($php);
        /** @var Expression $expression */
        $expression = $stmts[0];
        $this->assertEquals($output, $this->pathResolver->resolve($expression->expr, $path));
    }

    public function fixtures(): iterable
    {
        foreach (Finder::create()->in(__DIR__ . '/Fixtures')->name('path.*.fixture') as $file) {
            yield [(string)$file];
        }
    }
}
