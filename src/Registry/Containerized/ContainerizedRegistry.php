<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Registry\Containerized;

use Sbooker\CommandBus\Endpoint;
use Sbooker\CommandBus\Registry;
use Sbooker\CommandBus\TimeoutCalculator;

final class ContainerizedRegistry implements Registry
{
    /** @var CommandServiceRegistry */
    private $endpointContainer;

    /** @var CommandServiceRegistry */
    private $timeoutCalculatorContainer;

    /** @var CommandServiceRegistry|null */
    private $onSuccessInvokerContainer;

    /** @var CommandServiceRegistry|null */
    private $onFailInvokerContainer;

    public function __construct(
        CommandServiceRegistry $endpointContainer,
        CommandServiceRegistry $timeoutCalculatorContainer,
        ?CommandServiceRegistry $onSuccessInvokerContainer = null,
        ?CommandServiceRegistry $onFailInvokerContainer = null
    ) {
        $this->endpointContainer = $endpointContainer;
        $this->timeoutCalculatorContainer = $timeoutCalculatorContainer;
        $this->onSuccessInvokerContainer = $onSuccessInvokerContainer;
        $this->onFailInvokerContainer = $onFailInvokerContainer;
    }

    public function getEndpoint(string $commandName): Endpoint
    {
        return $this->get($this->endpointContainer, $commandName);
    }

    public function getTimeoutCalculator(string $commandName): TimeoutCalculator
    {
        return $this->get($this->timeoutCalculatorContainer, $commandName);
    }

    public function getOnSuccessInvoker(string $commandName): ?callable
    {
        if (null === $this->onSuccessInvokerContainer) {
            return null;
        }

        return $this->get($this->onSuccessInvokerContainer, $commandName);
    }

    public function getOnFailInvoker(string $commandName): ?callable
    {
        if (null === $this->onSuccessInvokerContainer) {
            return null;
        }

        return $this->get($this->onFailInvokerContainer, $commandName);
    }

    private function get(CommandServiceRegistry $adapter, string $commandName): ?object
    {
        return $adapter->get($commandName);
    }
}