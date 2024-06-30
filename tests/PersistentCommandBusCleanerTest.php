<?php

namespace Sbooker\CommandBus\Tests;

use Sbooker\CommandBus\CleanStorage;
use Sbooker\CommandBus\CommandBusCleaner;
use Sbooker\CommandBus\PersistentCommandBusCleaner;

final class PersistentCommandBusCleanerTest extends TestCase
{
    /**
     * @dataProvider examples
     */
    public function test(?string $inputSuccess, ?string $configuredSuccess, ?string $inputFail, ?string $configuredFail, ?string $expectedSuccess, ?string $expectedFail)
    {
        $cleaner = $this->createCleaner(
            $this->createStorage($expectedSuccess, $expectedFail),
            $configuredSuccess,
            $configuredFail
        );

        $cleaner->clean($this->makeDateTime($inputSuccess), $this->makeDateTime($inputFail));
    }

    public static function examples(): array
    {
        return [
            'all empty' => [ null, null, null, null, null, null, ],
            'input success only' => [ '2024-01-01 00:00:00', null, null, null, '2024-01-01 00:00:00', null ],
            'configured success only' => [ null, '2024-01-01 00:00:00', null, null, '2024-01-01 00:00:00', null ],
            'both success only' => [ '2024-01-01 00:00:00', '2023-01-01 00:00:00', null, null, '2024-01-01 00:00:00', null ],
            'input failed only' => [ null, null, '2024-01-01 00:00:00', null, null, '2024-01-01 00:00:00' ],
            'both input' => [ '2024-01-01 00:00:00', null, '2023-01-01 00:00:00', null, '2024-01-01 00:00:00', '2023-01-01 00:00:00' ],
            'configured success input failed' =>[ null, '2024-01-01 00:00:00', '2023-01-01 00:00:00', null, '2024-01-01 00:00:00', '2023-01-01 00:00:00' ],
            'both success only input failed' => [ '2024-01-01 00:00:00', '2023-01-01 00:00:00', '2022-01-01 00:00:00', null, '2024-01-01 00:00:00', '2022-01-01 00:00:00', ],
            'configured failed only' => [ null, null, null, '2024-01-01 00:00:00', null, '2024-01-01 00:00:00' ],
            'input success configured failed' => [ '2024-01-01 00:00:00', null, null, '2021-01-01 00:00:00', '2024-01-01 00:00:00', '2021-01-01 00:00:00' ],
            'configured success configured failed' => [ null, '2024-01-01 00:00:00', null, '2021-01-01 00:00:00', '2024-01-01 00:00:00', '2021-01-01 00:00:00' ],
            'both success configured failed' => [ '2024-01-01 00:00:00', '2023-01-01 00:00:00', null, '2021-01-01 00:00:00', '2024-01-01 00:00:00', '2021-01-01 00:00:00' ],
            'both failed only' => [ null, null, '2024-01-01 00:00:00', '2023-01-01 00:00:00', null, '2024-01-01 00:00:00', ],
            'input success both failed' => [ '2025-01-01 00:00:00', null, '2024-01-01 00:00:00', '2023-01-01 00:00:00', '2025-01-01 00:00:00', '2024-01-01 00:00:00', ],
            'configured success both failed' => [ null, '2025-01-01 00:00:00', '2024-01-01 00:00:00', '2023-01-01 00:00:00', '2025-01-01 00:00:00', '2024-01-01 00:00:00', ],
            'both success & failed' => [ '2026-01-01 00:00:00', '2025-01-01 00:00:00', '2024-01-01 00:00:00', '2023-01-01 00:00:00', '2026-01-01 00:00:00', '2024-01-01 00:00:00', ],
        ];
    }

    private function createCleaner(CleanStorage $storage, ?string $configuredSuccess, ?string $configuredFail): CommandBusCleaner
    {
        return new PersistentCommandBusCleaner(
            $storage,
            $this->makeDateTime($configuredSuccess),
            $this->makeDateTime($configuredFail),
        );
    }

    private function createStorage(?string $expectedSuccess, ?string $expectedFail): CleanStorage
    {
        $mock = $this->createMock(CleanStorage::class);
        $mock
            ->expects($this->exactly($this->resolveCount($expectedSuccess)))
            ->method('cleanSuccessCommands')
            ->with($this->makeDateTime($expectedSuccess));
        $mock
            ->expects($this->exactly($this->resolveCount($expectedFail)))
            ->method('cleanFailedCommands')
            ->with($this->makeDateTime($expectedFail));

        return $mock;
    }

    private function makeDateTime(?string $dateTime): ?\DateTimeImmutable
    {
        if (null === $dateTime) {
            return null;
        }

        return new \DateTimeImmutable($dateTime);
    }

    private function resolveCount(?string $dateTime): int
    {
        if (null === $dateTime) {
            return 0;
        }

        return 1;
    }
}