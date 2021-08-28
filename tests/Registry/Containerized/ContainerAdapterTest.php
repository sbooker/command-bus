<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Tests\Registry\Containerized;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sbooker\CommandBus\Registry\Containerized\ContainerAdapter;

class ContainerAdapterTest extends TestCase
{
    public function testNoDefaultService(): void
    {
        $serviceId = 'service';
        $service = new \stdClass();
        $adapter = new ContainerAdapter($this->createPsrContainer($serviceId, $service));

        $service = $adapter->get('command.name');

        $this->assertNull($service);
    }

    public function testDefaultService(): void
    {
        $serviceId = 'service';
        $expectedService = new \stdClass();
        $adapter = new ContainerAdapter($this->createPsrContainer($serviceId, $expectedService), $serviceId);

        $service = $adapter->get('command.name');

        $this->assertEquals($expectedService, $service);
    }

    public function testGetService(): void
    {
        $serviceId = 'service';
        $expectedService = new \stdClass();
        $adapter =
            new ContainerAdapter(
                $this->createPsrContainer($serviceId, $expectedService),
                null,
                ['command.name' => $serviceId]
            );

        $service = $adapter->get('command.name');

        $this->assertEquals($expectedService, $service);
    }

    public function testDefaultServiceNotExists(): void
    {
        $serviceId = 'service';
        $adapter =
            new ContainerAdapter(
                $this->createPsrContainer($serviceId),
                $serviceId
            );

        $this->expectException(\RuntimeException::class);
        $adapter->get('command.name');
    }

    public function testServiceNotExists(): void
    {
        $serviceId = 'service';
        $adapter =
            new ContainerAdapter(
                $this->createPsrContainer($serviceId),
                null,
                ['command.name' => $serviceId]
            );
        $this->expectException(\RuntimeException::class);
        $adapter->get('command.name');
    }

    private function createPsrContainer(string $serviceId, ?object $service = null): ContainerInterface
    {
        $mock = $this->createMock(ContainerInterface::class);
        $mock->method('has')->with($serviceId)->willReturn(null !== $service);
        $mock->method('get')->with($serviceId)->willReturn($service);

        return $mock;
    }
}