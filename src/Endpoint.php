<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

use Ramsey\Uuid\UuidInterface;

interface Endpoint
{
    public function process(UuidInterface $id, object $payload): void;
}