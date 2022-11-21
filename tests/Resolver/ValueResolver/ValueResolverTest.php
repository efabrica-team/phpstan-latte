<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Resolver\ValueResolver;

use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPStan\Testing\PHPStanTestCase;
use Symfony\Component\Finder\Finder;

final class ValueResolverTest extends PHPStanTestCase
{
    private ValueResolver $valueResolver;

    private Parser $phpParser;

    public function setUp(): void
    {
        $this->valueResolver = new ValueResolver();
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

        $stmts = $this->phpParser->parse($php);
        /** @var Expression $expression */
        $expression = $stmts[0];
        $this->assertEquals($output, $this->valueResolver->resolve($expression->expr, $path));
    }

    public function fixtures(): iterable
    {
        foreach (Finder::create()->in(__DIR__ . '/Fixtures')->name('*.fixture') as $file) {
            yield [(string)$file];
        }
    }
}
