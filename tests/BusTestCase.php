<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Tests;

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

    final protected function createTransactionManager(int $persistCounter = 0, object $toPersist = null, int $trasnactionCounter = 1): TransactionManager
    {
        return
            new TransactionManager(
                $this->createTransactionHandler($persistCounter, $toPersist, $trasnactionCounter)
            );
    }

    private function createTransactionHandler(int $persistCounter = 0, object $toPersist = null, int $trasnactionCounter = 1): TransactionHandler
    {
        $mock = $this->createMock(TransactionHandler::class);
        $mock->expects($this->exactly($trasnactionCounter))->method('begin');
        $mock->expects($this->exactly($persistCounter))->method('persist');
        $mock->expects($this->exactly($trasnactionCounter))->method('commit');
        $mock->expects($this->never())->method('rollback');

        return $mock;
    }
}