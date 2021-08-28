<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Tests;

use Ramsey\Uuid\Uuid;
use Sbooker\CommandBus\AttemptCounter;
use Sbooker\CommandBus\Command;
use Sbooker\CommandBus\Denormalizer;
use Sbooker\CommandBus\Endpoint;
use Sbooker\CommandBus\Invoker;
use Sbooker\CommandBus\NormalizedCommand;
use Sbooker\CommandBus\Normalizer;
use Sbooker\CommandBus\Registry;
use Sbooker\CommandBus\Status;
use Sbooker\CommandBus\TimeoutCalculator;

final class CommandTest extends TestCase
{
    public function testCreate(): void
    {
        $id = Uuid::uuid4();
        $name = 'command';
        $normalizedPayload = ['a' => 'a', 'b' => 'b '];
        $payload = (object)$normalizedPayload;
        $command = new Command($id, $payload, $this->createNormalizer($payload, $name, $normalizedPayload));

        $this->assertPropertyEquals($id, $command, 'id');
        $this->assertNormalizedCommand(new NormalizedCommand($name, $normalizedPayload), $command);
        $this->assertNull($command->getState()->getResult());
        $this->assertStatus(Status::created(), $command);
        $this->assertAttemptCounter(0, $command);
    }

    public function testSuccessExecute(): void
    {
        $id = Uuid::uuid4();
        $name = 'command';
        $normalizedPayload = ['a' => 'a', 'b' => 'b '];
        $payload = (object)$normalizedPayload;
        $command = new Command($id, $payload, $this->createNormalizer($payload, $name, $normalizedPayload));

        $command->execute(
            $this->getRegistry(
                $this->getSuccessEndpoint(),
                new TimeoutCalculator\Fix(10, 10),
                $this->getInvoker(1),
                $this->getInvoker(0)
            ),
            $this->createDenormalizer($normalizedPayload, $name, $payload)
        );

        $this->assertStatus(Status::success(), $command);
        $this->assertAttemptCounter(1, $command);
    }

    public function testErrorExecute(): void
    {
        $id = Uuid::uuid4();
        $name = 'command';
        $normalizedPayload = ['a' => 'a', 'b' => 'b '];
        $payload = (object)$normalizedPayload;
        $command = new Command($id, $payload, $this->createNormalizer($payload, $name, $normalizedPayload));
        $error = new \Exception("Some error");

        $command->execute(
            $this->getRegistry(
                $this->getFailEndpoint($error),
                new TimeoutCalculator\Fix(10, 10),
                $this->getInvoker(0),
                $this->getInvoker(0)
            ),
            $this->createDenormalizer($normalizedPayload, $name, $payload)
        );

        $this->assertStatus(Status::pending(), $command);
        $this->assertAttemptCounter(1, $command);
        $this->assertEquals(
            [
                'code' => $error->getCode(),
                'message' => $error->getMessage(),
                'class' => get_class($error),
                'trace' => $error->getTraceAsString(),
            ],
            $command->getState()->getResult()
        );
    }

    public function testAttemptEnded(): void
    {
        $id = Uuid::uuid4();
        $name = 'command';
        $normalizedPayload = ['a' => 'a', 'b' => 'b '];
        $payload = (object)$normalizedPayload;
        $command = new Command($id, $payload, $this->createNormalizer($payload, $name, $normalizedPayload));

        $command->execute(
            $this->getRegistry(
                $this->getNotCalledEndpoint(),
                new TimeoutCalculator\Fix(10, 0),
                $this->getInvoker(0),
                $this->getInvoker(1)
            ),
            $this->createDenormalizer($normalizedPayload, $name, $payload)
        );

        $this->assertStatus(Status::fail(), $command);
        $this->assertAttemptCounter(1, $command);
        $this->assertNull($command->getState()->getResult());
    }

    public function testAcceptWrongNamedCommand(): void
    {
        $commandId = Uuid::uuid4();
        $payload = new \stdClass();
        $normalizer = $this->createNormalizerNotRegisteredCommand($payload);
        $this->expectException(\RuntimeException::class);
        new Command($commandId, $payload, $normalizer);
    }

    private function getSuccessEndpoint(): Endpoint
    {
        $mock = $this->createMock(Endpoint::class);
        $mock->expects($this->once())->method('process');

        return $mock;
    }

    private function getFailEndpoint(\Throwable $error): Endpoint
    {
        $mock = $this->createMock(Endpoint::class);
        $mock->expects($this->once())->method('process')->willThrowException($error);

        return $mock;
    }

    private function getNotCalledEndpoint(): Endpoint
    {
        $mock = $this->createMock(Endpoint::class);
        $mock->expects($this->never())->method('process');

        return $mock;
    }

    private function getInvoker(int $count): Invoker
    {
        $mock = $this->createMock(Invoker::class);
        $mock->expects($this->exactly($count))->method('__invoke');

        return $mock;
    }

    private function getRegistry(Endpoint $endpoint, TimeoutCalculator $calculator, Invoker $onSuccess, Invoker $onFail): Registry
    {
        return new class ($endpoint, $calculator, $onSuccess, $onFail) implements Registry {
            private Endpoint $endpoint;
            private TimeoutCalculator $calculator;
            private Invoker $onSuccess;
            private Invoker $onFail;

            public function __construct(Endpoint $endpoint, TimeoutCalculator $calculator, Invoker $onSuccess, Invoker $onFail)
            {
                $this->endpoint = $endpoint;
                $this->calculator = $calculator;
                $this->onSuccess = $onSuccess;
                $this->onFail = $onFail;
            }

            public function getEndpoint(string $commandName): Endpoint
            {
                return $this->endpoint;
            }

            public function getTimeoutCalculator(string $commandName): TimeoutCalculator
            {
                return $this->calculator;
            }

            public function getOnSuccessInvoker(string $name): ?callable
            {
                return $this->onSuccess;
            }

            public function getOnFailInvoker(string $commandName): ?callable
            {
                return $this->onFail;
            }
        };
    }

    private function assertStatus(Status $status, Command $command): void
    {
        $this->assertTrue($status->equals($command->getState()->getStatus()));
    }

    private function assertNormalizedCommand(NormalizedCommand $expected, Command $command): void
    {
        $this->assertEquals($expected, $this->getPrivatePropertyValue($command, 'normalizedCommand'));
    }

    private function assertAttemptCounter(int $count, Command $command): void
    {
        /** @var AttemptCounter $counter */
        $counter = $this->getPrivatePropertyValue($command, 'attemptCounter');
        $this->assertEquals($count, $this->getPrivatePropertyValue($counter, 'count'));
    }

    private function createNormalizer(?object $expected, string $commandName, ?array $payload): Normalizer
    {
        $mock = $this->createMock(Normalizer::class);
        $mock->method('normalize')->with($expected)->willReturn(new NormalizedCommand($commandName, $payload));

        return $mock;
    }

    private function createNormalizerNotRegisteredCommand(?object $payload): Normalizer
    {
        $mock = $this->createMock(Normalizer::class);
        $mock->method('normalize')->with($payload)->willThrowException(new \RuntimeException());

        return $mock;
    }

    private function createDenormalizer(?array $normalized, string $name, ?object $payload): Denormalizer
    {
        $mock = $this->createMock(Denormalizer::class);
        $mock->method('denormalize')->with($normalized, $name)->willReturn($payload);

        return $mock;
    }
}