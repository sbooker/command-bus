<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\NameGiver;

use Ds\Map;
use Sbooker\CommandBus\ClassNameMapItem;
use Sbooker\CommandBus\NameGiver;

class ClassNameMap implements NameGiver
{
    /**
     * @var array Class::class => name
     */
    private array $map;

    public function __construct(array $map)
    {
        foreach ($map as $class => $name) {
            $this->addToMap($class, $name);
        }
    }

    public function add(ClassNameMapItem $item): void
    {
        $this->addToMap($item->getClass(), $item->getName());
    }

    private function addToMap(string $class, string $name): void
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException("Class $class does not exists");
        }
        if (isset($this->map[$class])) {
            throw new \InvalidArgumentException("Class $class already mapped");
        }

        $this->map[$class] = $name;
    }

    public function getName(object $command): string
    {
        $class = get_class($command);
        if (!isset($this->map[$class])) {
            throw new \RuntimeException("Name for class $class not found");
        }

        return $this->map[$class];

    }

    public function getClass(string $commandName): string
    {
        $reverseMap = array_flip($this->map);
        if (!isset($reverseMap[$commandName])) {
            throw new \RuntimeException("Class for name $commandName not found");
        }
        return $reverseMap[$commandName];
    }
}