<?php

declare(strict_types=1);

namespace Tests\Sbooker\CommandBus;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    final protected function assertPropertyEquals($expected, object $object, string $property): void
    {
        $this->assertEquals($expected, $this->getPrivatePropertyValue($object, $property));
    }

    final protected function openProperty(object $object, string $property): \ReflectionProperty
    {
        $ref = new \ReflectionProperty($object, $property);
        $ref->setAccessible(true);

        return $ref;
    }

    final protected function assertDateTimeBetween(\DateTimeInterface $before, \DateTimeInterface $after, \DateTimeInterface $value): void
    {
        $this->assertGreaterThanOrEqual($before, $value);
        $this->assertLessThanOrEqual($after, $value);
    }

    final protected function getPrivatePropertyValue(object $object, string $property)
    {
        return $this->openProperty($object, $property)->getValue($object);
    }
}