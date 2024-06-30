<?php

namespace Sbooker\CommandBus;
interface CleanStorage
{
    public function cleanSuccessCommands(\DateTimeImmutable $before): void;

    public function cleanFailedCommands(\DateTimeImmutable $before): void;
}