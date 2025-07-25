<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations;

use Nette\Utils\Finder;
use PHPUnit\Framework\Attributes\DataProvider;

final class CollectorResultForAnnotationsTest extends ScanCollectorResultTestCase
{
    protected static function additionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/../../../config.neon',
        ];
    }

    /**
     * @dataProvider fixtures
     */
    #[DataProvider('fixtures')]
    public function testFixture(string $fixtureName): void
    {
        $this->resolveFixture(
            __DIR__ . '/Fixtures/' . $fixtureName,
            __NAMESPACE__ . '\\Fixtures\\' . $fixtureName,
        );
    }

    public static function fixtures(): array
    {
        $fixtures = [];
        foreach (Finder::findDirectories('*')->in(__DIR__ . '/Fixtures') as $path) {
            $fixtures[] = [$path->getFilename()];
        }
        return $fixtures;
    }
}
