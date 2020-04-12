<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

interface Registry
{
    public function getEndpoint(string $commandName): Endpoint;

    public function getTimeoutCalculator(string $commandName): TimeoutCalculator;

    public function getOnSuccessInvoker(string $name): ?callable;

    public function getOnFailInvoker(string $commandName): ?callable;
}