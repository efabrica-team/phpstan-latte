<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule;

use Efabrica\PHPStanLatte\Rule\LatteTemplatesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

abstract class LatteTemplatesRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return $this->getContainer()->getByType(LatteTemplatesRule::class);
    }
}
