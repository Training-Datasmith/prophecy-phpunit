<?php

declare(strict_types=1);

namespace Prophecy\PhpUnit\Tests\Fixtures;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class NoProphecy extends TestCase
{
    use ProphecyTrait;

    public function testMethod()
    {
        // to avoid warnings/risky status
        $this->assertTrue(true);
    }
}
