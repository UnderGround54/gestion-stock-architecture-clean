<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use ReflectionClass;
use ReflectionException;

/**
 * Injecte des valeurs dans les propriétés privées/protégées des entités du Domain
 * via Reflection, sans passer par des setters publics.
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
        $this->reflect($object)->getProperty($property)->setValue($object, $value);
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
            $ref->getProperty($property)->setValue($object, $value);
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
