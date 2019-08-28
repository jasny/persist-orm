<?php

declare(strict_types=1);

namespace Jasny\DB\Gateway;

use Jasny\DB\CRUD\Result;
use Jasny\DB\ODM\ODMInterface;
use Jasny\Entity\EntityInterface;
use Jasny\Entity\IdentifiableEntityInterface;
use Jasny\EntityCollection\EntityCollectionInterface;
use Jasny\DB\Exception\EntityNotFoundException;

/**
 * Gateway to a data set, like a DB table (RDBMS) or collection (NoSQL).
 */
interface GatewayInterface
{
    /**
     * Create a new entity.
     *
     * @param mixed ...$args   Arguments are passed to entity constructor
     * @return EntityInterface
     */
    public function create(...$args): EntityInterface;

    /**
     * Fetch a single entity.
     *
     * @param mixed $id    ID or filter
     * @param array $opts
     * @return EntityInterface
     * @throws EntityNotFoundException if Entity with id isn't found and no 'optional' opt was given
     */
    public function find($id, array $opts = []): ?EntityInterface;

    /**
     * Fetch multiple entities
     *
     * @param array $filter
     * @param array $opts
     * @return EntityCollectionInterface<EntityInterface>
     */
    public function findAll(array $filter, array $opts = []): EntityCollectionInterface;

    /**
     * Check if an entity exists.
     *
     * @param mixed $id   ID or filter
     * @param array $opts
     * @return bool
     */
    public function exists($id, array $opts = []): bool;

    /**
     * Save an entity.
     *
     * @param EntityInterface $entity
     * @param array           $opts
     * @return void
     */
    public function save(EntityInterface $entity, array $opts = []): void;

    /**
     * Delete an entity.
     *
     * @param IdentifiableEntityInterface $entity
     * @param array                       $opts
     * @return void
     */
    public function delete(IdentifiableEntityInterface $entity, array $opts = []): void;


    /**
     * Find records based on filter.
     * (No ODM / ORM)
     *
     * @param array $filter
     * @param array $opts
     * @return Result
     */
    public function fetch(array $filter = [], array $opts = []): Result;

    /**
     * Query and count result.
     *
     * @param array $filter
     * @param array $opts
     * @return int
     */
    public function count(array $filter = [], array $opts = []): int;

    /**
     * Find records in the data source using full text search.
     * (No ODM / ORM)
     *
     * @param string $terms
     * @param array  $filter
     * @param array  $opts
     * @return Result
     */
    public function search(string $terms, array $filter = [], array $opts = []): Result;
}
