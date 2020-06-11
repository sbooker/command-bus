<?php

declare(strict_types=1);

namespace Tests\Sbooker\CommandBus\Infrastructure\Persistence;

use LitGroup\Enumerable\Enumerable;

final class TestDatabases extends Enumerable
{
    public static function mysql5(): self
    {
        return self::createEnum('mysql5');
    }

    public static function mysql8(): self
    {
        return self::createEnum('mysql8');
    }

    public static function postgresql12(): self
    {
        return self::createEnum('postgresql12');
    }
}