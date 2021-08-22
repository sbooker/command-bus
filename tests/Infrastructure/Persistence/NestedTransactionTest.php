<?php

declare(strict_types=1);

namespace Tests\Sbooker\CommandBus\Infrastructure\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
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
    public function testWithTrowsCommandProcessor(string $db): void
    {
        $em = $this->setUpDbDeps($db);
        $commandName = 'command.name';
        $commandId = Uuid::uuid4();
        $command = $this->createCommand($commandId, $commandName, '-1second');
        $repository = $this->getRepository($em);
        $this->makeFixtures($repository, $command);

        $this->createCommandHandler($commandName, $em)->handleNext();

        $commandAfterProcessing = $repository->get($commandId);
        $attemptCount = $commandAfterProcessing->getAttemptCount();
        $commandState = $commandAfterProcessing->getState();

        $this->assertEquals(1, $attemptCount, "Attempt count failures");
        $this->assertTrue($commandState->getStatus()->equals(Status::pending()));
        $this->assertEquals(self::ERROR_CODE, $commandState->getResult()['code']);
        $this->assertEquals(self::ERROR_MESSAGE, $commandState->getResult()['message']);

        $this->tearDownDbDeps($em);
    }

    private function createThrowsCommandProcessor(): Endpoint
    {
        return new CallableEndpoint(
            function (): void {
                $this->getTransactionManager()->transactional(function (): void {
                    throw new \Exception(self::ERROR_MESSAGE, self::ERROR_CODE);
                });
            }
        );
    }

    private function createCommandHandler(string $commandName, EntityManagerInterface $entityManager): PersistentCommandHandler
    {
        return new PersistentCommandHandler(
            $this->createRegistry($commandName),
            $this->createDenormalizer($commandName),
            $this->getRepository($entityManager),
            $this->getTransactionManager()
        );
    }

    private function createRegistry(string $commandName): Registry
    {
        $mock = $this->createMock(Registry::class);
        $mock->expects($this->once())
            ->method('getEndpoint')
            ->with($commandName)
            ->willReturn($this->createThrowsCommandProcessor());
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
}