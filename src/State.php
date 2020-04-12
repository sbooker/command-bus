<?php

declare(strict_types=1);

namespace Sbooker\CommandBus;

class State
{
    private Status $status;

    private ?array $result;

    public function __construct(Status $status, ?array $result)
    {
        $this->status = $status;
        $this->result = $result;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getResult(): ?array
    {
        return $this->result;
    }
}