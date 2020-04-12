<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

use Ramsey\Uuid\UuidInterface;

interface Handler
{
    public function handle(UuidInterface $id): void;

    public function handleNext(): bool;
}