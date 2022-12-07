<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\CollectorResultTest;
use Nette\Utils\Finder;

final class CollectorResultForPresenterTest extends CollectorResultTest
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
        $this->resolveFixture(
            __DIR__ . '/Fixtures/' . $fixtureName,
            __NAMESPACE__ . '\\Fixtures\\' . $fixtureName,
            [__DIR__ . '/Fixtures/ParentPresenter.php']
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
