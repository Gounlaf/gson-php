<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */
namespace Tebru\Gson\Test\Unit\Internal\AccessorStrategy;

use PHPUnit_Framework_TestCase;
use Tebru\Gson\Internal\AccessorStrategy\SetByNull;
use Tebru\Gson\Test\Mock\UserMock;

/**
 * Class SetByNullTest
 *
 * @author Nate Brunette <n@tebru.net>
 */
class SetByNullTest extends PHPUnit_Framework_TestCase
{
    public function testSetDoesNothing()
    {
        $mock = new UserMock();
        $strategy = new SetByNull();
        $strategy->set($mock, 1);

        self::assertEquals(new UserMock(), $mock);
    }
}
