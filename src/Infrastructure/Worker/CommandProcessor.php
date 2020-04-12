<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Infrastructure\Worker;

use Sbooker\CommandBus\Handler;
use Sbooker\EventLoopWorker\Workable;

final class CommandProcessor implements Workable
{
    private Handler $handler;

    public function __construct(Handler $handler)
    {
        $this->handler = $handler;
    }

    public function process(): bool
    {
        return $this->handler->handleNext();
    }
}