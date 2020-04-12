<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

use Ramsey\Uuid\UuidInterface;

interface CommandBus
{
    public function accept(UuidInterface $id, object $command): void;

    public function getState(UuidInterface $id): ?State;
}