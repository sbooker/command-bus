<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

final class Status extends \Sbooker\Workflow\Status
{
    public static function created(): self
    {
        return self::createEnum('created');
    }

    public static function pending(): self
    {
        return self::createEnum('pending');
    }

    public static function success(): self
    {
        return self::createEnum('success');
    }

    public static function fail(): self
    {
        return self::createEnum('fail');
    }
}