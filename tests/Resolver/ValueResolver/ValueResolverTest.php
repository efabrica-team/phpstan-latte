<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Resolver\ValueResolver;

use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Nette\Utils\Finder;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Testing\PHPStanTestCase;

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
        if ($output === 'null') {
            $output = null;
        } else {
            $output = array_map('trim', explode("\n", $output));
        }

        $stmts = $this->phpParser->parse($php);
        /** @var Expression $expression */
        $expression = $stmts[0];

        $typeSpecifier = self::getContainer()->getService('typeSpecifier');
        $scopeFactory = $this->createScopeFactory($this->createReflectionProvider(), $typeSpecifier);
        $scope = $scopeFactory->create(ScopeContext::create($path));

        $this->assertEquals($output, $this->valueResolver->resolve($expression->expr, $scope));
    }

    public function fixtures(): iterable
    {
        foreach (Finder::findFiles('path.*.fixture')->in(__DIR__ . '/Fixtures') as $file) {
            yield [(string)$file];
        }
    }
}
