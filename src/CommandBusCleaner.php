<?php

namespace Sbooker\CommandBus;

interface CommandBusCleaner
{
    public function clean(?\DateTimeImmutable $successBefore = null, ?\DateTimeImmutable $failedBefore = null): void;
}