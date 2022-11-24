<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Compiler;

use Efabrica\PHPStanLatte\Compiler\LatteToPhpCompiler;
use Latte\Engine;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Testing\PHPStanTestCase;

final class LatteToPhpCompilerTest extends PHPStanTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
            Engine::VERSION_ID < 30000 ? __DIR__ . '/../../latte2.neon' : __DIR__ . '/../../latte3.neon',
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

        $output = $compiler->compile($scope, $latteContent, [], []);
        $compiledPhpContent = Engine::VERSION_ID < 30000 ? $compiledPhpContentLatte2 : $compiledPhpContentLatte3;
        $this->assertStringMatchesFormat($compiledPhpContent, $output);
    }

    public function dataProvider(): iterable
    {
        yield [__DIR__ . '/Fixtures/foreach.php.inc'];
    }
}
