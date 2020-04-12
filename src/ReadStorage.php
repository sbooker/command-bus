<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

use Ramsey\Uuid\UuidInterface;

interface ReadStorage
{
    public function get(UuidInterface $id): ?Command;
}