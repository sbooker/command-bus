<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Registry\Containerized;

use Psr\Container\ContainerInterface;

final class ContainerAdapter implements CommandServiceRegistry
{
    /** @var ContainerInterface */
    private $container;

    /** @var string|null */
    private $defaultServiceId;

    /** @var array [string $name => string $id] */
    private $idMap;

    public function __construct(ContainerInterface $container, ?string $defaultServiceId = null, array $idMap = [])
    {
        $this->container = $container;
        $this->defaultServiceId = $defaultServiceId;
        $this->idMap = $idMap;
    }

    public function get(string $commandName): ?object
    {
        $id = $this->getIdFromMap($this->idMap, $commandName, $this->defaultServiceId);
        if (null === $id) {
            return null;
        }

        return $this->getFromContainer($id);
    }

    private function getFromContainer(string $id): object
    {
        if (!$this->container->has($id)) {
            throw new \RuntimeException("Required service '$id' not configured in container");
        }

        return $this->container->get($id);
    }

    private function getIdFromMap(array $map, string $name, ?string $default = null): ?string
    {
        return isset($map[$name]) ? $map[$name] : $default;
    }
}