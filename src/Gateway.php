<?php

declare(strict_types=1);

namespace Jasny\DB\Gateway;

use Improved as i;
use const Improved\FUNCTION_ARGUMENT_PLACEHOLDER as ___;
use Jasny\DB\ODM\EntityMapper;
use Jasny\DB\CRUD\CRUDInterface;
use Jasny\DB\CRUD\Result;
use Jasny\DB\Search\SearchInterface;
use Jasny\Entity\EntityInterface;
use Jasny\Entity\IdentifiableEntityInterface;
use Jasny\EntityCollection\EntityCollectionInterface;
use Jasny\DB\Exception\EntityNotFoundException;

/**
 * Base class for composite gateway.
 */
class Gateway implements GatewayInterface
{
    /**
     * @var mixed
     */
    protected $storage;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var EntityMapper
     */
    protected $entityMapper;

    /**
     * @var CRUDInterface
     */
    protected $crud;

    /**
     * @var SearchInterface
     */
    protected $search;


    /**
     * Gateway constructor.
     *
     * @param mixed           $storage
     * @param EntityMapper    $entityMapper
     * @param CRUDInterface   $crud
     * @param SearchInterface $search
     */
    public function __construct(
        $storage,
        string $entityClass,
        EntityMapper $entityMapper,
        CRUDInterface $crud,
        SearchInterface $search
    ) {
        $this->storage = $storage;

        $this->entityMapper = $entityMapper;
        $this->crud = $crud;
        $this->search = $search;
    }


    /**
     * Create a new entity.
     *
     * @param mixed ...$args
     * @return EntityInterface
     */
    public function create(...$args): EntityInterface
    {
        return $this->entityMapper->create($this->entityClass, ...$args);
    }

    /**
     * Fetch a single entity.
     *
     * @param mixed $id ID or filter
     * @param array $opts
     * @return EntityInterface
     * @throws EntityNotFoundException if Entity with id isn't found and no 'optional' opt was given
     */
    public function find($id, array $opts = []): ?EntityInterface
    {
        $filter = is_array($id) ? $id : [':id' => $id];

        try {
            $entity = $this->crud->fetch($this->storage, $filter, ['limit' => 1] + $opts)
                ->then($this->entityMapper->convert())
                ->first((bool)($opts['optional'] ?? false));
        } catch (RangeException $exception) {
            throw new EntityNotFoundException($this->entityClass, $id, 0, $exception);
        }

        return $entity;
    }

    /**
     * Fetch all entities from the set.
     *
     * @param array $filter
     * @param array $opts
     * @return EntityCollectionInterface|EntityInterface[]
     */
    public function findAll(array $filter = [], array $opts = []): EntityCollectionInterface
    {
        return $this->crud->fetch($this->storage, $filter, $opts)
            ->then($this->entityMapper->convert());
    }

    /**
     * Check if an exists in the collection.
     *
     * @param mixed $id ID or filter
     * @param array $opts
     * @return bool
     */
    public function exists($id, array $opts = []): bool
    {
        $filter = is_array($id) ? $id : [':id' => $id];

        return $this->crud->count($this->storage, $filter, ['limit' => 1] + $opts) > 0;
    }

    /**
     * Save an entity.
     *
     * @param EntityInterface $entity
     * @param array           $opts
     * @return void
     */
    public function save(EntityInterface $entity, array $opts = []): void
    {
        $persist = i\function_partial([$this->crud, 'save'], $this->storage, ___, $opts);

        $this->entityMapper->save($persist, $entity);
    }

    /**
     * Delete an entity.
     *
     * @param IdentifiableEntityInterface $entity
     * @param array $opts
     * @return void
     */
    public function delete(IdentifiableEntityInterface $entity, array $opts = []): void
    {
        $persist = function(array $ids) use ($opts) {
            $filter = count($ids) === 1 ? [':id' => reset($ids)] : [':ids' => $ids];
            $this->crud->delete($this->storage, $filter, $opts);
        };

        $this->entityMapper->delete($persist, $entity);
    }


    /**
     * Fetch data.
     * (No ODM/ORM)
     *
     * @param array $filter
     * @param array $opts
     * @return Result
     */
    public function fetch(array $filter = [], array $opts = []): Result
    {
        return $this->crud->fetch($this->storage, $filter, $opts);
    }

    /**
     * Fetch the number of items in the set.
     *
     * @param array $filter
     * @param array $opts
     * @return int
     */
    public function count(array $filter = [], array $opts = []): int
    {
        return $this->crud->count($this->storage, $filter, $opts);
    }

    /**
     * Full text search.
     * (No ODM/ORM)
     *
     * @param string $terms
     * @param array $filter
     * @param array $opts
     * @return Result
     */
    public function search(string $terms, array $filter = [], array $opts = []): Result
    {
        return $this->search->search($this->storage, $terms, $filter, $opts);
    }
}
