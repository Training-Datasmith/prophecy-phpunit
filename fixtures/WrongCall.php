<?php

declare(strict_types=1);

namespace Prophecy\PhpUnit\Tests\Fixtures;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class WrongCall extends TestCase
{
    use ProphecyTrait;

    public function testMethod()
    {
        $prophecy = $this->prophesize('stdClass');

        $prophecy->talk()->willReturn('Hello world!');
    }
}
