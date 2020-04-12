<?php

declare(strict_types=1);

namespace Tests\Sbooker\CommandBus;

use Ramsey\Uuid\Uuid;
use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\NameGiver;
use Sbooker\CommandBus\NormalizedCommand;
use Sbooker\CommandBus\Normalizer;
use Sbooker\CommandBus\PersistentCommandCommandBus;
use Sbooker\CommandBus\ReadStorage;
use Sbooker\CommandBus\Status;
use Sbooker\CommandBus\WriteStorage;
use Sbooker\TransactionManager\TransactionHandler;
use Sbooker\TransactionManager\TransactionManager;

final class PersistentCommandBusTest extends BusTestCase
{
    public function testAcceptExistsCommand(): void
    {
        $commandId = Uuid::uuid4();
        $payload = new \stdClass();
        $normalizer = $this->createNormalizer($payload);
        $command = new Command($commandId, $payload, $normalizer);
        $bus =
            new PersistentCommandCommandBus(
                $normalizer,
                $this->createAddWriteStorage(0, $command),
                $this->createTransactionManager(),
                $this->createReadStorage($command)
            );

        $bus->accept($commandId, $payload);
    }

    public function testAcceptNewCommand(): void
    {
        $commandId = Uuid::uuid4();
        $payload = new \stdClass();
        $normalizer = $this->createNormalizer($payload);
        $command = new Command($commandId, $payload, $normalizer);
        $bus =
            new PersistentCommandCommandBus(
                $normalizer,
                $this->createAddWriteStorage(1, $command),
                $this->createTransactionManager(),
                $this->createReadStorage(null)
            );

        $bus->accept($commandId, $payload);
    }

    public function testGetStateExistsCommand(): void
    {
        $commandId = Uuid::uuid4();
        $payload = new \stdClass();
        $normalizer = $this->createNormalizer($payload);
        $command = new Command($commandId, $payload, $normalizer);
        $bus =
            new PersistentCommandCommandBus(
                $normalizer,
                $this->createAddWriteStorage(0, $command),
                $this->createTransactionManager(),
                $this->createReadStorage($command)
            );

        $state = $bus->getState($commandId);

        $this->assertEquals($command->getState(), $state);
    }

    public function testGetStateNotExistsCommand(): void
    {
        $commandId = Uuid::uuid4();
        $payload = new \stdClass();
        $normalizer = $this->createNormalizer($payload);
        $command = new Command($commandId, $payload, $normalizer);
        $bus =
            new PersistentCommandCommandBus(
                $normalizer,
                $this->createAddWriteStorage(0, $command),
                $this->createTransactionManager(),
                $this->createReadStorage(null)
            );

        $state = $bus->getState($commandId);

        $this->assertNull($state);
    }

    private function createAddWriteStorage(int $calls, Command $command): WriteStorage
    {
        $mock = $this->createMock(WriteStorage::class);
        $mock->expects($this->exactly($calls))->method('add');

        return $mock;
    }

    final protected function createNormalizer(?object $payload): Normalizer
    {
        $mock = $this->createMock(Normalizer::class);
        $mock->method('normalize')->with($payload)->willReturn(new NormalizedCommand('command.name', []));

        return $mock;
    }
}