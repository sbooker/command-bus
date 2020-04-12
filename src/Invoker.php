<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

use Ramsey\Uuid\UuidInterface;

interface Invoker
{
    public function __invoke(UuidInterface $id, string $name, ?array $result);
}