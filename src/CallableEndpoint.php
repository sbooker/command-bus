<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

use Ramsey\Uuid\UuidInterface;

final class CallableEndpoint implements Endpoint
{
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function process(UuidInterface $id, object $payload): void
    {
        $callable = $this->callable;

        $callable($payload);
    }
}