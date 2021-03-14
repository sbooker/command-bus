<?php

declare(strict_types=1);

namespace Tests\Sbooker\CommandBus;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\Denormalizer;
use Sbooker\CommandBus\Endpoint;
use Sbooker\CommandBus\NormalizedCommand;
use Sbooker\CommandBus\Normalizer;
use Sbooker\CommandBus\PersistentCommandHandler;
use Sbooker\CommandBus\Registry;
use Sbooker\CommandBus\TimeoutCalculator;
use Sbooker\CommandBus\WriteStorage;

class PersistentCommandHandlerTest extends BusTestCase
{
    public function testHandleNextNoCommands(): void
    {
        $commandName = 'command.name';
        $names = [$commandName];

        $handler =
            new PersistentCommandHandler(
                $this->createRegistry(0, $commandName),
                $this->createDenormalizer(null, $commandName, null),
                $this->createGetFirstToProcessAndLockWriteStorage($names, null),
                $this->createTransactionManager(),
                $names
            );

        $result = $handler->handleNext();

        $this->assertFalse($result);
    }

    /**
     * @dataProvider namesConfigurationExamples
     */
    public function testHandleNextWithCommands(string $commandName, array $names): void
    {
        $commandId = Uuid::uuid4();
        $payload = new \stdClass();
        $normalizedPayload = (array)$payload;
        $command = new Command($commandId, $payload, $this->createNormalizer($payload, $commandName, $normalizedPayload));

        $handler =
            new PersistentCommandHandler(
                $this->createRegistry(1, $commandName),
                $this->createDenormalizer($normalizedPayload, $commandName, $payload),
                $this->createGetFirstToProcessAndLockWriteStorage($names, $command),
                $this->createTransactionManager(),
                $names
            );

        $result = $handler->handleNext();

        $this->assertTrue($result);
    }

    public function namesConfigurationExamples(): array
    {
        return [
            [ 'command.name', ['command.name']],
            [ 'command.name', []],
        ];
    }

    public function testHandleNoCommands(): void
    {
        $commandId = Uuid::uuid4();
        $commandName = 'command.name';
        $names = [$commandName];

        $handler =
            new PersistentCommandHandler(
                $this->createRegistry(0, $commandName),
                $this->createDenormalizer(null, $commandName, null),
                $this->createGetAndLockWriteStorage($names, $commandId, null),
                $this->createTransactionManager(),
                $names
            );

        $handler->handle($commandId);
    }

    public function testHandleWithCommands(): void
    {
        $commandId = Uuid::uuid4();
        $commandName = 'command.name';
        $names = [$commandName];
        $payload = new \stdClass();
        $normalizedPayload = (array)$payload;
        $command = new Command($commandId, $payload, $this->createNormalizer($payload, $commandName, $normalizedPayload));

        $handler =
            new PersistentCommandHandler(
                $this->createRegistry(1, $commandName),
                $this->createDenormalizer($normalizedPayload, $commandName, $payload),
                $this->createGetAndLockWriteStorage($names, $commandId, $command),
                $this->createTransactionManager(),
                $names
            );

        $handler->handle($commandId);
    }

    private function createGetAndLockWriteStorage(array $names, UuidInterface $commandId, ?Command $command): WriteStorage
    {
        $mock = $this->createMock(WriteStorage::class);
        $mock->expects($this->once())->method('getAndLock')->with($names, $commandId)->willReturn($command);

        return $mock;
    }

    private function createGetFirstToProcessAndLockWriteStorage(array $names, ?Command $command): WriteStorage
    {
        $mock = $this->createMock(WriteStorage::class);
        $mock->expects($this->once())
            ->method('getFirstToProcessAndLock')
            ->with($names)
            ->willReturn($command);

        return $mock;
    }

    private function createRegistry(int $count, string $commandName): Registry
    {
        $mock = $this->createMock(Registry::class);
        $mock->expects($this->exactly($count))
            ->method('getEndpoint')
            ->with($commandName)
            ->willReturn($this->createEndpoint($count));
        $mock->expects($this->exactly($count))
            ->method('getTimeoutCalculator')
            ->with($commandName)
            ->willReturn($this->createTimeoutCalculator($count));

        return $mock;
    }

    private function createEndpoint(int $count): Endpoint
    {
        $mock = $this->createMock(Endpoint::class);
        $mock->expects($this->exactly($count))->method('process');

        return $mock;
    }

    private function createTimeoutCalculator(int $count): TimeoutCalculator
    {
        $mock = $this->createMock(TimeoutCalculator::class);
        $mock->expects($this->exactly($count))->method('getMaxAttempts')->willReturn(1);
        $mock->expects($this->exactly($count))->method('calculate')->willReturn(1);

        return $mock;
    }

    private function createNormalizer(?object $expected, string $commandName, ?array $payload): Normalizer
    {
        $mock = $this->createMock(Normalizer::class);
        $mock->method('normalize')->with($expected)->willReturn(new NormalizedCommand($commandName, $payload));

        return $mock;
    }

    private function createDenormalizer(?array $normalized, string $name, ?object $payload): Denormalizer
    {
        $mock = $this->createMock(Denormalizer::class);
        $mock->method('denormalize')->with($normalized, $name)->willReturn($payload);

        return $mock;
    }
}