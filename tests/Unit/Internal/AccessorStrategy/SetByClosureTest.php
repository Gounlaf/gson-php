<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */

namespace Tebru\Gson\Test\Unit\Internal\AccessorStrategy;

use PHPUnit_Framework_TestCase;
use Tebru\Gson\Internal\AccessorStrategy\SetByClosure;
use Tebru\Gson\Test\Mock\ClosureTestMock;

/**
 * Class SetByClosureTest
 *
 * @author Nate Brunette <n@tebru.net>
 */
class SetByClosureTest extends PHPUnit_Framework_TestCase
{
    public function testSetter()
    {
        $mock = new ClosureTestMock();
        $strategy = new SetByClosure('foo', ClosureTestMock::class);
        $strategy->set($mock, 'bar2');

        self::assertAttributeSame('bar2', 'foo', $mock);
    }

    public function testSetterNoProperty()
    {
        $mock = new ClosureTestMock();
        $strategy = new SetByClosure('foo2', ClosureTestMock::class);
        $strategy->set($mock, 'bar');

        self::assertAttributeSame('bar', 'foo2', $mock);
    }
}
