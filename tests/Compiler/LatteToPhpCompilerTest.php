<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Compiler;

use Efabrica\PHPStanLatte\Compiler\LatteToPhpCompiler;
use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Testing\PHPStanTestCase;

final class LatteToPhpCompilerTest extends PHPStanTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
            __DIR__ . '/../config.neon',
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

        [$latteContent, $compiledPhpContentLatte2, $compiledPhpContentLatte3] = array_map('trim', explode('-----', file_get_contents($path) ?: '', 3));

        $output = $compiler->compile('', $latteContent, [], []);
        $compiledPhpContent = LatteVersion::isLatte2() ? $compiledPhpContentLatte2 : $compiledPhpContentLatte3;
        $this->assertStringMatchesFormat($compiledPhpContent, $output);
    }

    public function dataProvider(): iterable
    {
        yield [__DIR__ . '/Fixtures/foreach.php.inc'];
    }
}
