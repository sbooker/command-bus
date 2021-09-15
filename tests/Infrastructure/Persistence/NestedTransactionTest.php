<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Tests\Infrastructure\Persistence;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sbooker\CommandBus\CallableEndpoint;
use Sbooker\CommandBus\Denormalizer;
use Sbooker\CommandBus\Endpoint;
use Sbooker\CommandBus\PersistentCommandHandler;
use Sbooker\CommandBus\Registry;
use Sbooker\CommandBus\Status;
use Sbooker\CommandBus\TimeoutCalculator;

final class NestedTransactionTest extends PersistenceTestCase
{
    private const ERROR_MESSAGE = 'message';
    private const ERROR_CODE = 123;

    /**
     * @dataProvider dbs
     */
    public function testWithPersistAndTrowsCommandProcessor(string $db): void
    {
        $em = $this->setUpDbDeps($db);
        $commandName = 'command.name';
        $commandId = Uuid::uuid4();
        $command = $this->createCommand($commandId, $commandName, '-1second');
        $repository = $this->getRepository($em);
        $this->makeFixtures($command);
        $entityId = Uuid::uuid4();
        $endpoint = $this->createPersistAndThrowsCommandProcessor($entityId);

        $this->createCommandHandler($commandName, $em, $endpoint)->handleNext();

        $commandAfterProcessing = $repository->get($commandId);
        $attemptCount = $commandAfterProcessing->getAttemptCount();
        $commandState = $commandAfterProcessing->getState();
        $storedEntity = $this->getStoredTestEntity($em, $entityId);

        $this->assertEquals(1, $attemptCount, "Attempt count failures");
        $this->assertTrue($commandState->getStatus()->equals(Status::pending()));
        $this->assertEquals(self::ERROR_CODE, $commandState->getResult()['code']);
        $this->assertEquals(self::ERROR_MESSAGE, $commandState->getResult()['message']);
        $this->assertNull($storedEntity);

        $this->tearDownDbDeps($em);
    }

    private function createPersistAndThrowsCommandProcessor(UuidInterface $entityId): Endpoint
    {
        return new CallableEndpoint(
            function () use ($entityId): void {
                $this->getTransactionManager()->transactional(function () use ($entityId): void {
                    $this->getTransactionManager()->persist(new TestEntity($entityId, 'value'));
                    throw new \Exception(self::ERROR_MESSAGE, self::ERROR_CODE);
                });
            }
        );
    }

    /**
     * @dataProvider dbs
     */
    public function testWithPersistCommandProcessor(string $db): void
    {
        $em = $this->setUpDbDeps($db);
        $commandName = 'command.name';
        $commandId = Uuid::uuid4();
        $command = $this->createCommand($commandId, $commandName, '-1second');
        $repository = $this->getRepository($em);
        $this->makeFixtures($command);
        $entityId = Uuid::uuid4();
        $value = 'value';
        $endpoint = $this->createPersistCommandProcessor($entityId, $value);

        $this->createCommandHandler($commandName, $em, $endpoint)->handleNext();

        $commandAfterProcessing = $repository->get($commandId);
        $attemptCount = $commandAfterProcessing->getAttemptCount();
        $commandState = $commandAfterProcessing->getState();
        $storedEntity = $this->getStoredTestEntity($em, $entityId);

        $this->assertEquals(1, $attemptCount, "Attempt count failures");
        $this->assertTrue($commandState->getStatus()->equals(Status::success()));
        $this->assertEquals( $value, $storedEntity->getValue());

        $this->tearDownDbDeps($em);
    }

    private function createPersistCommandProcessor(UuidInterface $entityId, string $value): Endpoint
    {
        return new CallableEndpoint(
            function () use ($entityId, $value): void {
                $this->getTransactionManager()->transactional(function () use ($entityId, $value): void {
                    $this->getTransactionManager()->persist(new TestEntity($entityId, $value));
                });
            }
        );
    }

    /**
     * @dataProvider dbs
     */
    public function testWithSaveAndTrowsCommandProcessor(string $db): void
    {
        $em = $this->setUpDbDeps($db);
        $commandName = 'command.name';
        $commandId = Uuid::uuid4();
        $command = $this->createCommand($commandId, $commandName, '-1second');
        $repository = $this->getRepository($em);
        $entityId = Uuid::uuid4();
        $value = 'oldValue';
        $entity = new TestEntity($entityId, $value);
        $this->makeFixtures($command, $entity);
        $endpoint = $this->createSaveAndThrowsCommandProcessor($em, $entityId, 'newValue');

        $this->createCommandHandler($commandName, $em, $endpoint)->handleNext();

        $commandAfterProcessing = $repository->get($commandId);
        $attemptCount = $commandAfterProcessing->getAttemptCount();
        $commandState = $commandAfterProcessing->getState();
        $storedEntity = $this->getStoredTestEntity($em, $entityId);

        $this->assertEquals(1, $attemptCount, "Attempt count failures");
        $this->assertTrue($commandState->getStatus()->equals(Status::pending()));
        $this->assertEquals(self::ERROR_CODE, $commandState->getResult()['code']);
        $this->assertEquals(self::ERROR_MESSAGE, $commandState->getResult()['message']);
        $this->assertEquals($value, $storedEntity->getValue());

        $this->tearDownDbDeps($em);
    }

    private function createSaveAndThrowsCommandProcessor(EntityManagerInterface $em, UuidInterface $entityId, string $newValue): Endpoint
    {
        return new CallableEndpoint(
            function () use ($em, $entityId, $newValue): void {
                $this->getTransactionManager()->transactional(function () use ($em, $entityId, $newValue): void {
                    $entity = $this->getLockedStoredTestEntity($entityId);
                    $entity->setValue($newValue);
                    $this->getTransactionManager()->save($entity);
                    throw new \Exception(self::ERROR_MESSAGE, self::ERROR_CODE);
                });
            }
        );
    }

    /**
     * @dataProvider dbs
     */
    public function testWithSaveCommandProcessor(string $db): void
    {
        $em = $this->setUpDbDeps($db);
        $commandName = 'command.name';
        $commandId = Uuid::uuid4();
        $command = $this->createCommand($commandId, $commandName, '-1second');
        $repository = $this->getRepository($em);
        $entityId = Uuid::uuid4();
        $entity = new TestEntity($entityId, 'oldValue');
        $this->makeFixtures($command, $entity);
        $value = 'newValue';
        $endpoint = $this->createSaveCommandProcessor($entityId, $value);

        $this->createCommandHandler($commandName, $em, $endpoint)->handleNext();

        $commandAfterProcessing = $repository->get($commandId);
        $attemptCount = $commandAfterProcessing->getAttemptCount();
        $commandState = $commandAfterProcessing->getState();
        $storedEntity = $this->getStoredTestEntity($em, $entityId);

        $this->assertEquals(1, $attemptCount, "Attempt count failures");
        $this->assertTrue($commandState->getStatus()->equals(Status::success()));
        $this->assertEquals($value, $storedEntity->getValue());

        $this->tearDownDbDeps($em);
    }

    private function createSaveCommandProcessor(UuidInterface $entityId, string $newValue): Endpoint
    {
        return new CallableEndpoint(
            function () use ($entityId, $newValue): void {
                $this->getTransactionManager()->transactional(function () use ($entityId, $newValue): void {
                    $entity = $this->getLockedStoredTestEntity($entityId);
                    $entity->setValue($newValue);
                });
            }
        );
    }

    /**
     * @dataProvider dbs
     */
    public function testWithNotSaveCommandProcessor(string $db): void
    {
        $em = $this->setUpDbDeps($db);
        $commandName = 'command.name';
        $commandId = Uuid::uuid4();
        $command = $this->createCommand($commandId, $commandName, '-1second');
        $repository = $this->getRepository($em);
        $entityId = Uuid::uuid4();
        $oldValue = 'oldValue';
        $entity = new TestEntity($entityId, $oldValue);
        $this->makeFixtures($command, $entity);
        $value = 'newValue';
        $endpoint = $this->createNotSaveCommandProcessor($em, $entityId, $value);

        $this->createCommandHandler($commandName, $em, $endpoint)->handleNext();

        $commandAfterProcessing = $repository->get($commandId);
        $attemptCount = $commandAfterProcessing->getAttemptCount();
        $commandState = $commandAfterProcessing->getState();
        $storedEntity = $this->getStoredTestEntity($em, $entityId);

        $this->assertEquals(1, $attemptCount, "Attempt count failures");
        $this->assertTrue($commandState->getStatus()->equals(Status::success()));
        $this->assertEquals($oldValue, $storedEntity->getValue());

        $this->tearDownDbDeps($em);
    }

    private function createNotSaveCommandProcessor(EntityManagerInterface $em, UuidInterface $entityId, string $newValue): Endpoint
    {
        return new CallableEndpoint(
            function () use ($em, $entityId, $newValue): void {
                $this->getTransactionManager()->transactional(function () use ($em, $entityId, $newValue): void {
                    $entity = $em->find(TestEntity::class, $entityId, LockMode::PESSIMISTIC_WRITE);
                    $entity->setValue($newValue);
                });
            }
        );
    }

    private function createCommandHandler(string $commandName, EntityManagerInterface $entityManager, Endpoint $endpoint): PersistentCommandHandler
    {
        return new PersistentCommandHandler(
            $this->createRegistry($commandName, $endpoint),
            $this->createDenormalizer($commandName),
            $this->getRepository($entityManager),
            $this->getTransactionManager()
        );
    }

    private function createRegistry(string $commandName, Endpoint $endpoint): Registry
    {
        $mock = $this->createMock(Registry::class);
        $mock->expects($this->once())
            ->method('getEndpoint')
            ->with($commandName)
            ->willReturn($endpoint);
        $mock->expects($this->once())
            ->method('getTimeoutCalculator')
            ->with($commandName)
            ->willReturn($this->createTimeoutCalculator());

        return $mock;
    }

    private function createTimeoutCalculator(): TimeoutCalculator
    {
        $mock = $this->createMock(TimeoutCalculator::class);
        $mock->expects($this->once())->method('getMaxAttempts')->willReturn(10);
        $mock->expects($this->once())->method('calculate')->willReturn(1);

        return $mock;
    }

    private function createDenormalizer(string $name): Denormalizer
    {
        $mock = $this->createMock(Denormalizer::class);
        $mock->method('denormalize')->with(null, $name)->willReturn((object)[]);

        return $mock;
    }

    private function getStoredTestEntity(EntityManagerInterface $em, UuidInterface $id): ?TestEntity
    {
        return $em->getRepository(TestEntity::class)->find($id);
    }

    private function getLockedStoredTestEntity(UuidInterface $id): ?TestEntity
    {
        return $this->getTransactionManager()->getLocked(TestEntity::class, $id);
    }
}