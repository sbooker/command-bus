<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

interface Denormalizer
{
    public function denormalize(?array $data, string $name): ?object;
}