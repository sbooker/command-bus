<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

final class AttemptCounter
{
    /** @var int */
    private $count;

    /** @var \DateTimeImmutable  */
    private $nextAttemptAt;

    final public function __construct()
    {
        $this->count = 0;
        $this->nextAttemptAt = new \DateTimeImmutable();
    }

    /**
     * @throws MaxAttemptReached
     */
    public function nextAttempt(TimeoutCalculator $calculator): void
    {
        $this->increaseCount($calculator);
        if ($this->getCount() > $calculator->getMaxAttempts()) {
            throw new MaxAttemptReached();
        }
    }

    private function increaseCount(TimeoutCalculator $calculator): void
    {
        $this->count+= 1;
        $this->nextAttemptAt = $this->calculateNextAttemptTime($calculator);
    }

    private function calculateNextAttemptTime(TimeoutCalculator $calculator): \DateTimeImmutable
    {
        return new \DateTimeImmutable("+ {$calculator->calculate($this->getCount())} seconds");
    }

    private function getCount(): int
    {
        return $this->count;
    }
}