<?php

declare(strict_types=1);

namespace Jasny\EntityMapper;

use Jasny\Entity\EntityInterface;
use Improved\IteratorPipeline\Pipeline;
use Improved\IteratorPipeline\PipelineBuilder;

/**
 * Service for converting data into entities and visa versa.
 */
interface EntityMapperInterface
{
    /**
     * Create a new entity.
     *
     * @param string $class
     * @param mixed  ...$args  Arguments are passed to entity constructor
     * @return EntityInterface
     */
    public function create(string $class, ...$args): EntityInterface;

    /**
     * Turn data into entities.
     * @curry
     *
     * @param string          $class
     * @param iterable<array> $data
     * @return PipelineBuilder|Pipeline|iterable<EntityInterface>
     */
    public function convert(string $class, iterable $data = null);


    /**
     * Save entities to persistent storage.
     *
     * @param iterable<EntityInterface>|EntityInterface $entities
     * @return void
     */
    public function save($entities): void;

    /**
     * Delete entities from persistent storage.
     *
     * @param iterable<EntityInterface>|EntityInterface $entities
     * @return void
     */
    public function delete($entities): void;
}
