<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\EngineBootstrap;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteTemplatesRuleTest;
use Nette\Utils\Finder;

final class LatteTemplatesRuleForEngineBootstrapTest extends LatteTemplatesRuleTest
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/../../../config.neon',
            __DIR__ . '/config.neon',
        ];
    }

    /**
     * @dataProvider fixtures
     */
    public function testFixture(string $fixtureName): void
    {
        $this->analyseFixture(
            __DIR__ . '/Fixtures/' . $fixtureName,
            __NAMESPACE__ . '\\Fixtures\\' . $fixtureName,
        );
    }

    public function fixtures()
    {
        $fixtures = [];
        foreach (Finder::findDirectories()->in(__DIR__ . '/Fixtures') as $path) {
            $fixtures[] = [$path->getFilename()];
        }
        return $fixtures;
    }
}
