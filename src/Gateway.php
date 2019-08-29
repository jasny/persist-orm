<?php

declare(strict_types=1);

namespace Jasny\Persist;

use Improved as i;
use Jasny\DB\Option\LimitOption;
use Jasny\DB\Read\ReadInterface;
use Jasny\DB\Write\WriteInterface;
use Jasny\Entity\Entity;
use Jasny\Persist\Exception\NotFoundException;

/**
 * Base class for composite gateway.
 *
 * @template OBJ as object
 */
class Gateway implements GatewayInterface
{
    /**
     * @var string
     */
    protected $objectClass;

    /**
     * @var ObjectMapper
     */
    protected $mapper;

    /**
     * @var ReadInterface
     */
    protected $read;

    /**
     * @var WriteInterface
     */
    protected $write;


    /**
     * Gateway constructor.
     *
     * @param OBJ::class     $class
     * @param ReadInterface  $read
     * @param WriteInterface $write
     */
    public function __construct(string $class, ReadInterface $read, WriteInterface $write)
    {
        $this->objectClass = $class;
        $this->read = $read;
        $this->write = $write;

        $this->mapper = new ObjectMapper();
    }


    /**
     * Create a copy of the gateway with a different mapper.
     *
     * @param ObjectMapper $mapper
     * @return static
     */
    public function withObjectMapper(ObjectMapper $mapper): self
    {
        if ($this->mapper === $mapper) {
            return $this;
        }
    
        $copy = clone $this;
        $copy->mapper = $mapper;

        return $copy;
    }


    /**
     * Create a new object.
     *
     * @param mixed ...$args
     * @return OBJ
     */
    public function create(...$args): object
    {
        return $this->mapper->create($this->objectClass, ...$args);
    }

    /**
     * Fetch a single object.
     *
     * @param mixed $id    ID or filter
     * @param array $opts
     * @return OBJ
     * @throws NotFoundException
     */
    public function findOne($id, array $opts = []): object
    {
        $filter = is_array($id) ? $id : $this->mapper->filterOnId($id);
        $opts[] = new LimitOption(1);

        try {
            $object = $this->read->fetch($this->storage, $filter, $opts)
                ->then($this->mapper->convert())
                ->first(false);
        } catch (RangeException $exception) {
            throw new NotFoundException($this->objectClass, $id, 0, $exception);
        }

        return $object;
    }

    /**
     * Fetch a single object if it exists.
     *
     * @param mixed $id    ID or filter
     * @param array $opts
     * @return OBJ|null
     */
    public function findFirst($id, array $opts = []): ?object
    {
        $filter = is_array($id) ? $id : $this->mapper->filterOnId($id);
        $opts[] = new LimitOption(1);

        return $this->read->fetch($filter, $opts)
            ->then($this->mapper->convert())
            ->first(true);
    }

    /**
     * Fetch all objects from the set.
     *
     * @param array $filter
     * @param array $opts
     * @return IteratorPipeline<OBJ>
     */
    public function findAll(array $filter = [], array $opts = []): IteratorPipeline
    {
        return $this->read->fetch($filter, $opts)
            ->then($this->mapper->convert());
    }

    /**
     * Check if an object exists in the db.
     *
     * @param mixed $id    ID or filter
     * @param array $opts
     * @return bool
     */
    public function exists($id, array $opts = []): bool
    {
        $filter = is_array($id) ? $id : $this->mapper->filterOnId($id);

        return $this->read->count($filter, ['limit' => 1] + $opts) > 0;
    }

    /**
     * Check if the property of an object is unique.
     *
     * @param OBJ             $object
     * @param string|string[] $property  Property/properties that should be unique
     * @param array    $opts
     * @return bool
     */
    public function hasUnique($object, $property, array $opts = []): bool
    {
        i\type_check($object, $this->objectClass);

        $properties = is_iterable($property) ? $property : [$property];
        $filter = $this->mapper->filterExclude($object);
        
        foreach ($properties as $prop) {
            $filter[$prop] = $object->{$prop} ?? null;
        }

        $opts[] = new LimitOption(1);

        return $this->read->count($filter, $opts) > 0;
    }

    /**
     * Save an object.
     *
     * @param OBJ|iterable<OBJ> $object
     * @param array             $opts
     */
    public function save($object, array $opts = []): void
    {
        $objects = is_iterable($object) ? $object : [$object];

        $persist = function (array $data) use ($opts) {
            $this->write->save($data, $opts);
        };

        $this->mapper->save($this->objectClass, $persist, $objects);
    }

    /**
     * Delete an object.
     *
     * @param OBJ|iterable<OBJ> $object
     * @param array             $opts
     */
    public function delete($object, array $opts = []): void
    {
        $objects = is_iterable($object) ? $object : [$object];

        $persist = function (array $ids) use ($opts) {
            $filter = $this->mapper->filterOnId($ids, 'any');
            $this->write->delete($filter, $opts);
        };

        $this->mapper->delete($this->objectClass, $persist, $objects);
    }
}
