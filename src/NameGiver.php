<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

interface NameGiver
{
    /**
     * @throws \RuntimeException
     */
    public function getName(object $command): string;

    /**
     * @throws \RuntimeException
     */
    public function getClass(string $commandName): string;
}