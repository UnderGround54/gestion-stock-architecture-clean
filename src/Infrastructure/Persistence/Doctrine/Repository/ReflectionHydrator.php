<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use ReflectionClass;
use ReflectionException;

/**
 * Centralises ReflectionClass property injection for Domain entities.
 */
final class ReflectionHydrator
{
    /** @var array<class-string, ReflectionClass<object>> */
    private array $cache = [];

    /**
     * @template T of object
     * @param object $object
     * @param string $property
     * @param mixed $value
     * @throws ReflectionException
     */
    public function set(object $object, string $property, mixed $value): void
    {
        $prop = $this->reflect($object)->getProperty($property);
        $prop->setAccessible(true); // requis pour les props private/protected
        $prop->setValue($object, $value);
    }

    /**
     * @template T of object
     * @param object $object
     * @param array<string, mixed> $map ['propertyName' => $value, ...]
     * @throws ReflectionException
     */
    public function setMany(object $object, array $map): void
    {
        $ref = $this->reflect($object);

        foreach ($map as $property => $value) {
            $prop = $ref->getProperty($property);
            $prop->setAccessible(true);   // requis pour les props private/protected
            $prop->setValue($object, $value);
        }
    }

    /**
     * @template T of object
     * @param object $object
     * @return ReflectionClass<T>
     */
    private function reflect(object $object): ReflectionClass
    {
        return $this->cache[$object::class] ??= new ReflectionClass($object);
    }
}
