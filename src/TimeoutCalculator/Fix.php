<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\TimeoutCalculator;

use Sbooker\CommandBus\TimeoutCalculator;

class Fix implements TimeoutCalculator
{
    /** @var int */
    private $timeout;

    /** @var int */
    private $maxAttempts;

    public function __construct(int $timeout, int $maxAttempts)
    {
        $this->timeout = $timeout;
        $this->maxAttempts = $maxAttempts;
    }

    public function calculate(int $attemptsNumber): int
    {
        return $this->timeout;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }
}