<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Tests;

use Ramsey\Uuid\Uuid;
use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\NormalizedCommand;
use Sbooker\CommandBus\Normalizer;
use Sbooker\CommandBus\PersistentCommandBus;

final class PersistentCommandBusTest extends BusTestCase
{
    public function testAcceptExistsCommand(): void
    {
        $commandId = Uuid::uuid4();
        $payload = new \stdClass();
        $normalizer = $this->createNormalizer($payload);
        $command = new Command($commandId, $payload, $normalizer);
        $bus =
            new PersistentCommandBus(
                $normalizer,
                $this->createTransactionManager(0, $command, 0),
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
            new PersistentCommandBus(
                $normalizer,
                $this->createTransactionManager(1, $command),
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
            new PersistentCommandBus(
                $normalizer,
                $this->createTransactionManager(0, $command, 0),
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
            new PersistentCommandBus(
                $normalizer,
                $this->createTransactionManager(0, $command, 0),
                $this->createReadStorage(null)
            );

        $state = $bus->getState($commandId);

        $this->assertNull($state);
    }

    final protected function createNormalizer(?object $payload): Normalizer
    {
        $mock = $this->createMock(Normalizer::class);
        $mock->method('normalize')->with($payload)->willReturn(new NormalizedCommand('command.name', []));

        return $mock;
    }
}