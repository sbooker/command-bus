<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Tests\Infrastructure\Persistence;

use Ramsey\Uuid\UuidInterface;

class TestEntity
{
    private UuidInterface $id;
    private string $value;

    public function __construct(UuidInterface $id, string $value)
    {
        $this->id = $id;
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}