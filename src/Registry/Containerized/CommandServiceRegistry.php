<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Registry\Containerized;

interface CommandServiceRegistry
{
    public function get(string $commandName): ?object;
}