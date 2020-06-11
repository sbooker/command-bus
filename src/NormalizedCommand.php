<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

final class NormalizedCommand
{
    /** @var string */
    private $name;

    /** @var array|null */
    private $payload;

    public function __construct(string $name, ?array $payload)
    {
        $this->name = $name;
        $this->payload = $payload;
    }

    public function denormalizeWith(Denormalizer $denormalizer): ?object
    {
        return $denormalizer->denormalize($this->payload, $this->name);
    }

    public function getName(): string
    {
        return $this->name;
    }
}