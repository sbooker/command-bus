<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Infrastructure\Worker;

use Sbooker\CommandBus\Handler;
use Sbooker\EventLoopWorker\TimedWorker;
use Sbooker\EventLoopWorker\TimedWorkerFactory;
use Sbooker\EventLoopWorker\Workable;

final class WorkerFactory
{
    /** @var TimedWorkerFactory */
    private $timedWorkerFactory;

    public function __construct(TimedWorkerFactory $timedWorkerFactory)
    {
        $this->timedWorkerFactory = $timedWorkerFactory;
    }

    public function createPeriodic(Handler $handler, float $timeout): TimedWorker
    {
        return $this->timedWorkerFactory->createPeriodic($this->wrapHandler($handler), $timeout);
    }

    public function createPermanent(Handler $handler, float $timeout): TimedWorker
    {
        return $this->timedWorkerFactory->createPermanent($this->wrapHandler($handler), $timeout);
    }

    public function createDoubled(Handler $handler, float $minimalTimeout = 0.1, float $maximalTimeout = 300.0, float $initialTimeout = 1.0): TimedWorker
    {
        return $this->timedWorkerFactory->createDoubled($this->wrapHandler($handler), $minimalTimeout, $maximalTimeout, $initialTimeout);
    }

    private function wrapHandler(Handler $handler): Workable
    {
        return new CommandProcessor($handler);
    }
}