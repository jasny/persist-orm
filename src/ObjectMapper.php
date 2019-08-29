<?php

declare(strict_types=1);

namespace Jasny\Persist;

use InvalidArgumentException;
use Improved as i;
use Improved\IteratorPipeline\Pipeline;
use Improved\IteratorPipeline\PipelineBuilder;
use Jasny\Persist\ObjectMapper\SavePipeline;
use Jasny\Persist\ObjectMapper\DeletePipeline;
use ReflectionClass;
use function Jasny\object_set_properties;

/**
 * Service for converting data into objects and visa versa.
 */
class ObjectMapper implements ObjectMapperInterface
{
    /**
     * @var PipelineBuilder
     */
    protected $savePipeline;

    /**
     * @var PipelineBuilder
     */
    protected $deletePipeline;


    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->savePipeline = new SavePipeline();
        $this->deletePipeline = new DeletePipeline();
    }

    /**
     * Create service with a custom pipeline to save objects.
     *
     * @param PipelineBuilder $pipeline
     * @return static
     */
    public function withSave(PipelineBuilder $pipeline)
    {
        if ($this->savePipeline === $pipeline) {
            return $this;
        }

        $clone = clone $this;
        $clone->savePipeline = $pipeline;

        return $clone;
    }

    /**
     * Create service with a custom pipeline to delete objects.
     *
     * @param PipelineBuilder $pipeline
     * @return static
     */
    public function withDelete(PipelineBuilder $pipeline)
    {
        if ($this->deletePipeline === $pipeline) {
            return $this;
        }

        $clone = clone $this;
        $clone->deletePipeline = $pipeline;

        return $clone;
    }


    /**
     * Return a filter for the identifier.
     *
     * @param mixed  $id
     * @param string operation
     * @return array
     */
    public function filterOnId($id, $operation = null): array
    {
        $key = 'id' . ($operation !== null ? "({$operation})" : '');

        return [$key => $id];
    }

    /**
     * Return a filter to exclude the given object.
     *
     * @param object $object
     * @return array
     */
    public function filterExclude($object): array
    {
        if (!isset($object->id)) {
            throw new InvalidArgumentException("Object doesn't have an 'id' property");
        }

        return $this->filterOnId($object->id);
    }


    /**
     * Create a new Object.
     *
     * @param string $class    Object class
     * @param mixed  ...$args  Arguments are passed to class constructor
     * @return object
     */
    public function create(string $class, ...$args): object
    {
        if (!is_a($class, Object::class, true)) {
            throw new \InvalidArgumentException("$class isn't an Object");
        }

        return new $class(...$args);
    }

    /**
     * Turn data into objects.
     * Returns (callable) pipeline builder if data is omitted.
     *
     * @param string          $class  Object class
     * @param iterable<array> $data
     * @return PipelineBuilder|Pipeline|iterable<Object>
     * @throws \ReflectionException
     */
    public function convert(string $class, iterable $data = null)
    {
        if (!is_a($class, Object::class, true)) {
            throw new \InvalidArgumentException("$class isn't an Object");
        }

        if (method_exists($class, '__set_state')) {
            $createObject = function (array $values) use ($class) {
                return $class::__set_state($values);
            };
        } else {
            $refl = new ReflectionClass($class);

            $createObject = function (array $values) use ($refl) {
                $object = $refl->newInstanceWithoutConstructor();
                object_set_properties($object, $values);

                if (method_exists($object, '__construct')) {
                    $object->__construct();
                }

                return $object;
            };
        }

        return (isset($data) ? Pipeline::with($data) : Pipeline::build())
            ->expectType('array')
            ->map($createObject);
    }

    /**
     * Save objects to the database.
     *
     * @param string   $class  Object class
     * @param callable $persist
     * @param iterable $objects
     * @return void
     */
    public function save(string $class, callable $persist, iterable $objects): void
    {
        $this->savePipeline
            ->unstub('type_check', 'Improved\iterable_expect_type', $class)
            ->unstub('persist', $persist)
            ->with($objects)
            ->walk();
    }

    /**
     * Delete objects from the database.
     *
     * @param string   $class  Object class
     * @param callable $persist
     * @param iterable $objects
     * @return void
     */
    public function delete(string $class, callable $persist, iterable $objects): void
    {
        $this->deletePipeline
            ->unstub('type_check', 'Improved\iterable_expect_type', $class)
            ->unstub('persist', $persist)
            ->with($objects)
            ->walk();
    }
}
