<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Infrastructure\Persistence\Mapping;

use Sbooker\DoctrineEnumerableType\EnumerableType;
use Sbooker\CommandBus\Status;

final class StatusType extends EnumerableType
{
    protected function getEnumClass(): string
    {
        return Status::class;
    }

    public function getName()
    {
        return 'command_status';
    }
}