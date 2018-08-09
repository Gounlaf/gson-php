<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */

namespace Tebru\Gson\Test\Unit\Internal\TypeAdapter\Factory;


use PHPUnit\Framework\TestCase;
use Tebru\Gson\Internal\TypeAdapter\IntegerTypeAdapter;
use Tebru\Gson\Internal\TypeAdapter\Factory\IntegerTypeAdapterFactory;

use Tebru\Gson\Test\MockProvider;
use Tebru\PhpType\TypeToken;

/**
 * Class IntegerTypeAdapterFactoryTest
 *
 * @author Nate Brunette <n@tebru.net>
 * @covers \Tebru\Gson\Internal\TypeAdapter\Factory\IntegerTypeAdapterFactory
 */
class IntegerTypeAdapterFactoryTest extends TestCase
{
    public function testValidSupports(): void
    {
        $factory = new IntegerTypeAdapterFactory();

        self::assertTrue($factory->supports(new TypeToken('int')));
    }

    public function testInvalidSupports(): void
    {
        $factory = new IntegerTypeAdapterFactory();

        self::assertFalse($factory->supports(new TypeToken('string')));
    }

    public function testCreate(): void
    {
        $factory = new IntegerTypeAdapterFactory();
        $adapter = $factory->create(new TypeToken('int'), MockProvider::typeAdapterProvider());

        self::assertInstanceOf(IntegerTypeAdapter::class, $adapter);
    }
}
