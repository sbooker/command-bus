<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

interface Normalizer
{
    public function normalize(object $command): NormalizedCommand;
}