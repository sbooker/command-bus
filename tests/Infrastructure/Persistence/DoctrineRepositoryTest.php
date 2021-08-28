<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Tests\Infrastructure\Persistence;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\CommandBus\AttemptCounter;
use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\Workflow;

class DoctrineRepositoryTest extends PersistenceTestCase
{
    /**
     * @dataProvider dbs
     */
    public function testGet(string $db): void
    {
        $em = $this->setUpDbDeps($db);

        $repository = $this->getRepository($em);
        $commandId = Uuid::uuid4();
        $commandName = 'command.name';
        $expectedCommand = $this->createCommand($commandId, $commandName);
        $this->makeFixtures($expectedCommand);

        $command = $repository->get($commandId);

        $this->assertCommandEquals($expectedCommand, $command);

        $this->tearDownDbDeps($em);
    }

    /**
     * @dataProvider dbs
     */
    public function testGetAndLock(string $db): void
    {
        $em = $this->setUpDbDeps($db);

        $repository = $this->getRepository($em);
        $commandId = Uuid::uuid4();
        $commandName = 'command.name';
        $expectedCommand = $this->createCommand($commandId, $commandName);
        $this->makeFixtures($expectedCommand);

        $command=
            $this->getTransactionManager()->transactional(function () use ($repository, $commandId, $commandName) {
                return $repository->getAndLock([$commandName, 'other.command'], $commandId);
            });

        $this->assertCommandEquals($expectedCommand, $command);

        $this->tearDownDbDeps($em);
    }

    /**
     * @dataProvider dbs
     */
    public function testGetFirstToProcessAndLock(string $db): void
    {
        $em = $this->setUpDbDeps($db);

        $repository = $this->getRepository($em);

        $expectedCommandName = 'command.name';
        $otherCommandName = 'other.command.name';
        $expectedCommand = $this->createCommand(Uuid::uuid4(), $expectedCommandName, '-10seconds');
        $secondCommand = $this->createCommand(Uuid::uuid4(), $otherCommandName, '-5seconds');
        $this->makeFixtures($expectedCommand);
        $this->makeFixtures($secondCommand);

        $command=
            $this->getTransactionManager()->transactional(function () use ($repository, $expectedCommandName, $otherCommandName) {
                return $repository->getFirstToProcessAndLock([$expectedCommandName, $otherCommandName]);
            });

        $this->assertCommandEquals($expectedCommand, $command);

        $this->tearDownDbDeps($em);
    }

    private function assertCommandEquals(Command $expected, Command $given): void
    {
        $this->assertUuidEquals(
            $this->getPrivatePropertyValue($expected, 'id'),
            $this->getPrivatePropertyValue($given, 'id'),
        );

        foreach (['normalizedCommand', 'result'] as $property) {
            $this->assertSamePropertyEquals($expected, $given, $property);
        }

        $this->assertWorkflowEquals(
            $this->getPrivatePropertyValue($expected, 'workflow'),
            $this->getPrivatePropertyValue($given, 'workflow'),
        );
        $this->assertAttemptCounterEquals(
            $this->getPrivatePropertyValue($expected, 'attemptCounter'),
            $this->getPrivatePropertyValue($given, 'attemptCounter'),
        );
    }

    private function assertSamePropertyEquals(object $expected, object $given, string $property): void
    {
        $this->assertPropertyEquals($this->getPrivatePropertyValue($expected, $property), $given, $property);
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
            $this->getPrivatePropertyValue($given, $property),
        );
    }

    private function assertDateTimeEquals(\DateTimeInterface $expected, \DateTimeInterface $given): void
    {
        $this->assertEquals($expected->getTimestamp(), $given->getTimestamp());
    }
}