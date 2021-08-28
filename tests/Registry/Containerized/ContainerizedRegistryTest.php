<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Tests\Registry\Containerized;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use Sbooker\CommandBus\Endpoint;
use Sbooker\CommandBus\Invoker;
use Sbooker\CommandBus\Registry\Containerized\CommandServiceRegistry;
use Sbooker\CommandBus\Registry\Containerized\ContainerizedRegistry;
use Sbooker\CommandBus\TimeoutCalculator;

class ContainerizedRegistryTest extends TestCase
{
    /**
     * @dataProvider invokerExamples
     */
    public function test(?Invoker $expectedOnSuccess, ?Invoker $expectedOnFail): void
    {
        $commandName = 'command.name';
        $expectedEndpoint = $this->createEndpoint();
        $expectedTimeoutCalculator = $this->createTimeoutCalculator();
        $registry =
            new ContainerizedRegistry(
                $this->createContainerAdapter($commandName, $expectedEndpoint),
                $this->createContainerAdapter($commandName, $expectedTimeoutCalculator),
                $this->createContainerAdapter($commandName, $expectedOnSuccess),
                $this->createContainerAdapter($commandName, $expectedOnFail),
            );

        $endpoint = $registry->getEndpoint($commandName);
        $timeoutCalculator = $registry->getTimeoutCalculator($commandName);
        $onSuccess = $registry->getOnSuccessInvoker($commandName);
        $onFail = $registry->getOnFailInvoker($commandName);

        $this->assertEquals($expectedEndpoint, $endpoint);
        $this->assertEquals($expectedTimeoutCalculator, $timeoutCalculator);
        $this->assertEquals($expectedOnSuccess, $onSuccess);
        $this->assertEquals($expectedOnFail, $onFail);
    }

    public function invokerExamples(): array
    {
        return [
            [ new OnSuccess(), new OnFail() ],
            [ null, new OnFail() ],
            [ new OnSuccess(), null ],
            [ null, null ],
        ];
    }

    private function createContainerAdapter(string $commandName, ?object $service): CommandServiceRegistry
    {
        $mock = $this->createMock(CommandServiceRegistry::class);
        $mock->method('get')->with($commandName)->willReturn($service);

        return $mock;
    }

    private function createEndpoint(): Endpoint
    {
        return new class implements Endpoint {
            public function process(UuidInterface $id, object $payload): void { /*_*/ }
        };
    }

    private function createTimeoutCalculator(): TimeoutCalculator
    {
        return new class implements TimeoutCalculator {

            public function calculate(int $attemptsNumber): int
            {
                return 10;
            }

            public function getMaxAttempts(): int
            {
                return 3;
            }
        };
    }
}

final class OnSuccess implements Invoker {
    public function __invoke(UuidInterface $id, string $name, ?array $result) { /*_*/ }
}

final class OnFail implements Invoker {
    public function __invoke(UuidInterface $id, string $name, ?array $result) { /*_*/ }
}
