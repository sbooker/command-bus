<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

use Ramsey\Uuid\UuidInterface;

interface WriteStorage
{
    public function getAndLock(array $names, UuidInterface $id): ?Command;

    public function getFirstToProcessAndLock(array $names): ?Command;
}