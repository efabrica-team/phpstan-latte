<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Compiler;

use Efabrica\PHPStanLatte\Compiler\LatteToPhpCompiler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Testing\PHPStanTestCase;

final class LatteToPhpCompilerTest extends PHPStanTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function test(string $path): void
    {
        $container = self::getContainer();
        /** @var LatteToPhpCompiler $compiler */
        $compiler = $container->getByType(LatteToPhpCompiler::class);
        $scope = $this->createMock(MutatingScope::class);

        [$latteContent, $compiledPhpContent] = array_map('trim', explode('-----', file_get_contents($path) ?: '', 2));

        $output = $compiler->compile($scope, $latteContent, [], []);
        $this->assertSame($compiledPhpContent, $output);
    }

    public function dataProvider(): iterable
    {
        yield [__DIR__ . '/Fixtures/foreach.php.inc'];
    }
}
