<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\NameGiver;

use Ds\Map;
use Sbooker\CommandBus\NameGiver;

class ClassNameMap implements NameGiver
{
    /**
     * @var array Class::class => name
     */
    private array $map;

    public function __construct(array $map)
    {
        $this->map = $map;
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