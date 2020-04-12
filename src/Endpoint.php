<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

interface Endpoint
{
    public function process(object $payload): void;
}