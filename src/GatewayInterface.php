<?php

declare(strict_types=1);

namespace Jasny\Persist;

use Jasny\DB\Read\ReadInterface;
use Jasny\DB\Write\WriteInterface;
use Jasny\DB\Exception\FoundException;

/**
 * Gateway to a data set, like a DB table (RDBMS) or collection (NoSQL).
 *
 * @template OBJ as object
 */
interface GatewayInterface
{
    /**
     * Create a new object.
     *
     * @param mixed ...$args
     * @return OBJ
     */
    public function create(...$args): object;

    /**
     * Fetch a single object.
     *
     * @param mixed $id    ID or filter
     * @param array $opts
     * @return OBJ
     * @throws NotFoundException
     */
    public function findOne($id, array $opts = []): object;

    /**
     * Fetch a single object if it exists.
     *
     * @param mixed $id    ID or filter
     * @param array $opts
     * @return OBJ|null
     */
    public function findFirst($id, array $opts = []): ?object;

    /**
     * Fetch all objects from the set.
     *
     * @param array $filter
     * @param array $opts
     * @return IteratorPipeline<OBJ>
     */
    public function findAll(array $filter = [], array $opts = []): IteratorPipeline;

    /**
     * Check if an object exists in the db.
     *
     * @param mixed $id    ID or filter
     * @param array $opts
     * @return bool
     */
    public function exists($id, array $opts = []): bool;

    /**
     * Check if the property of an object is unique.
     *
     * @param OBJ             $object
     * @param string|string[] $property  Property/properties that should be unique
     * @param array    $opts
     * @return bool
     */
    public function hasUnique($object, $property, array $opts = []): bool;

    /**
     * Save an object.
     *
     * @param OBJ|iterable<OBJ> $object
     * @param array             $opts
     */
    public function save($object, array $opts = []): void;

    /**
     * Delete an object.
     *
     * @param OBJ   $object
     * @param array $opts
     */
    public function delete($object, array $opts = []): void;
}
