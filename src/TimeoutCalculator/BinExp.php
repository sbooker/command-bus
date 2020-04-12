<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\TimeoutCalculator;

use Sbooker\CommandBus\TimeoutCalculator;

final class BinExp implements TimeoutCalculator
{
    private int $maxAttempts;

    public function __construct(int $maxAttempts)
    {
        $this->maxAttempts = $maxAttempts;
    }

    public function calculate(int $attemptsNumber): int
    {
        return pow(2, $attemptsNumber);
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }
}