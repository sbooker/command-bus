<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

interface TimeoutCalculator
{
    public function calculate(int $attemptsNumber): int;

    public function getMaxAttempts(): int;
}