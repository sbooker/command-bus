<?php

declare(strict_types=1);

namespace Tests\Sbooker\CommandBus;

use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\NameGiver;
use Sbooker\CommandBus\NormalizedCommand;
use Sbooker\CommandBus\Normalizer;
use Sbooker\CommandBus\ReadStorage;
use Sbooker\CommandBus\WriteStorage;
use Sbooker\TransactionManager\TransactionHandler;
use Sbooker\TransactionManager\TransactionManager;

abstract class BusTestCase extends \PHPUnit\Framework\TestCase
{
    final protected function createNameGiver(int $calls, ?string $name = null): NameGiver
    {
        $mock = $this->createMock(NameGiver::class);
        $mock->expects($this->exactly($calls))->method('getName')->willReturn($name);

        return $mock;
    }

    final protected function createReadStorage(?Command $command): ReadStorage
    {
        $mock = $this->createMock(ReadStorage::class);
        $mock->expects($this->once())->method('get')->willReturn($command);

        return $mock;
    }

    final protected function createTransactionManager(): TransactionManager
    {
        return
            new TransactionManager(
                new class implements TransactionHandler {
                    public function begin(): void { }
                    public function commit(): void { }
                    public function rollBack(): void { }
                    public function clear(): void { }
                }
            );
    }
}