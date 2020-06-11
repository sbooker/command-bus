<?php

declare(strict_types=1);

namespace Tests\Sbooker\CommandBus\Infrastructure\Persistence;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\CommandBus\AttemptCounter;
use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\NormalizedCommand;
use Sbooker\CommandBus\Normalizer;
use Sbooker\CommandBus\ReadStorage;
use Sbooker\CommandBus\Workflow;
use Sbooker\CommandBus\WriteStorage;
use Sbooker\TransactionManager\TransactionManager;
use Tests\Sbooker\CommandBus\TestCase;

abstract class StorageTest extends TestCase
{
    /** @var TransactionManager */
    protected $transactionManager;

    abstract protected function setUpDbDeps(TestDatabases $db): void;

    abstract protected function tearDownDbDeps(): void;

    abstract protected function getReadStorage(): ReadStorage;

    abstract protected function getWriteStorage(): WriteStorage;

    /**
     * @dataProvider dbs
     */
    public function testGet(TestDatabases $db): void
    {
        $this->setUpDbDeps($db);

        $repository = $this->getReadStorage();
        $commandId = Uuid::uuid4();
        $commandName = 'command.name';
        $expectedCommand = $this->createCommand($commandId, $commandName);
        $this->makeFixtures($this->getWriteStorage(), $expectedCommand);

        $command = $repository->get($commandId);

        $this->assertCommandEquals($expectedCommand, $command);

        $this->tearDownDbDeps();
    }

    /**
     * @dataProvider dbs
     */
    public function testGetAndLock(TestDatabases $db): void
    {
        $this->setUpDbDeps($db);

        $repository = $this->getWriteStorage();
        $commandId = Uuid::uuid4();
        $commandName = 'command.name';
        $expectedCommand = $this->createCommand($commandId, $commandName);
        $this->makeFixtures($repository, $expectedCommand);

        $command=
            $this->transactionManager->transactional(function () use ($repository, $commandId, $commandName) {
                return $repository->getAndLock([$commandName, 'other.command'], $commandId);
            });

        $this->assertCommandEquals($expectedCommand, $command);

        $this->tearDownDbDeps();
    }

    /**
     * @dataProvider dbs
     */
    public function testGetFirstToProcessAndLock(TestDatabases $db): void
    {
        $this->setUpDbDeps($db);

        $repository = $this->getWriteStorage();

        $expectedCommandName = 'command.name';
        $otherCommandName = 'other.command.name';
        $expectedCommand = $this->createCommand(Uuid::uuid4(), $expectedCommandName, '-10seconds');
        $secondCommand = $this->createCommand(Uuid::uuid4(), $otherCommandName, '-5seconds');
        $this->makeFixtures($repository, $expectedCommand);
        $this->makeFixtures($repository, $secondCommand);

        $command=
            $this->transactionManager->transactional(function () use ($repository, $expectedCommandName, $otherCommandName) {
                return $repository->getFirstToProcessAndLock([$expectedCommandName, $otherCommandName]);
            });

        $this->assertCommandEquals($expectedCommand, $command);

        $this->tearDownDbDeps();
    }

    public function dbs(): array
    {
        return [
            [ TestDatabases::postgresql12() ],
            [ TestDatabases::mysql5() ],
            [ TestDatabases::mysql8() ],
        ];
    }

    private function createCommand(UuidInterface $commandId, string $commandName, string $nextAttemptAt = 'now'): Command
    {
        $command = new Command($commandId, new \stdClass(), $this->createNormalizer($commandName));
        $workflow = $this->getPrivatePropertyValue($command, 'attemptCounter');
        $this->openProperty($workflow, 'nextAttemptAt')->setValue($workflow, new \DateTimeImmutable($nextAttemptAt));

        return $command;
    }

    private function createNormalizer(string $commandName): Normalizer
    {
        return new class ($commandName) implements Normalizer {
            /** @var string */
            private $commandName;

            public function __construct(string $commandName)
            {
                $this->commandName = $commandName;
            }

            public function normalize(object $command): NormalizedCommand
            {
                return new NormalizedCommand($this->commandName, null);
            }
        };
    }

    private function makeFixtures(WriteStorage $repository, Command $expectedCommand): void
    {
        $this->transactionManager->transactional(function () use ($repository, $expectedCommand) {
            $repository->add($expectedCommand);
        });
    }

    private function assertCommandEquals(Command $expected, Command $given): void
    {
        $this->assertUuidEquals(
            $this->getPrivatePropertyValue($expected, 'id'),
            $this->getPrivatePropertyValue($given, 'id')
        );

        foreach (['normalizedCommand', 'result'] as $property) {
            $this->assertSamePropertyEquals($expected, $given, $property);
        }

        $this->assertWorkflowEquals(
            $this->getPrivatePropertyValue($expected, 'workflow'),
            $this->getPrivatePropertyValue($given, 'workflow')
        );
        $this->assertAttemptCounterEquals(
            $this->getPrivatePropertyValue($expected, 'attemptCounter'),
            $this->getPrivatePropertyValue($given, 'attemptCounter')
        );
    }

    private function assertUuidEquals(UuidInterface $expected, UuidInterface $given): void
    {
        $this->assertEquals($expected->toString(), $given->toString());
    }

    private function assertWorkflowEquals(Workflow $expected, Workflow $given): void
    {
        $this->assertTrue($expected->getStatus()->equals($given->getStatus()));
        $this->assertDateTimeEquals($expected->getChangedAt(), $given->getChangedAt());
    }

    private function assertAttemptCounterEquals(AttemptCounter $expected, AttemptCounter $given): void
    {
        $this->assertSamePropertyEquals($expected, $given, 'count');
        $this->assertDateTimePropertyEquals($expected, $given, 'nextAttemptAt');
    }

    private function assertDateTimePropertyEquals(object $expected, object $given, string $property): void
    {
        $this->assertDateTimeEquals(
            $this->getPrivatePropertyValue($expected, $property),
            $this->getPrivatePropertyValue($given, $property)
        );
    }

    private function assertDateTimeEquals(\DateTimeInterface $expected, \DateTimeInterface $given): void
    {
        $this->assertEquals($expected->getTimestamp(), $given->getTimestamp());
    }

    private function assertSamePropertyEquals(object $expected, object $given, string $property): void
    {
        $this->assertPropertyEquals($this->getPrivatePropertyValue($expected, $property), $given, $property);
    }
}