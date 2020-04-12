<?php

declare(strict_types=1);

namespace Tests\Sbooker\CommandBus;

use Sbooker\CommandBus\AttemptCounter;
use Sbooker\CommandBus\TimeoutCalculator;
use Sbooker\CommandBus\MaxAttemptReached;

final class AttemptCounterTest extends TestCase
{
    public function testMaxAttemptReached(): void
    {
        $counter = new AttemptCounter();

        $this->expectException(MaxAttemptReached::class);
        $counter->nextAttempt(new TimeoutCalculator\Fix(0, 0));
    }

    public function testCreate(): void
    {
        $before = new \DateTimeImmutable();
        $counter = new AttemptCounter();
        $after = new \DateTimeImmutable();

        $this->assertCounter($counter, 0, $before, $after);
    }

    public function testNextAttemptAt(): void
    {
        $timeout = 10;
        $counter = new AttemptCounter();

        $before = new \DateTimeImmutable();
        $counter->nextAttempt(new TimeoutCalculator\Fix($timeout, 10));
        $after = new \DateTimeImmutable();

        $this->assertCounter($counter, 1, $before->modify("+$timeout seconds"), $after->modify("+$timeout seconds"));
    }

    private function assertCounter(AttemptCounter $counter, int $countValue, \DateTimeImmutable $before, \DateTimeImmutable $after)
    {
        $nextAttemptAt = $this->getPrivatePropertyValue($counter, 'nextAttemptAt');

        $this->assertPropertyEquals($countValue, $counter, 'count');
        $this->assertDateTimeBetween($before, $after, $nextAttemptAt);
    }
}