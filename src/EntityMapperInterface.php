<?php

declare(strict_types=1);

namespace Jasny\EntityMapper;

use Jasny\Entity\Entity;
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
     * @return Entity
     */
    public function create(string $class, ...$args): Entity;

    /**
     * Turn data into entities.
     * Returns (callable) pipeline builder if data is omitted.
     *
     * @param string          $class
     * @param iterable<array> $data
     * @return PipelineBuilder|Pipeline|iterable<Entity>
     */
    public function convert(string $class, iterable $data = null);


    /**
     * Save entities to persistent storage.
     *
     * @param callable                                  $persist
     * @param iterable<Entity>|Entity $entities
     * @return void
     */
    public function save(callable $persist, $entities): void;

    /**
     * Delete entities from persistent storage.
     *
     * @param callable                                  $persist
     * @param iterable<Entity>|Entity $entities
     * @return void
     */
    public function delete(callable $persist, $entities): void;
}
