<?php

declare(strict_types=1);

namespace Jasny\EntityMapper;

use Improved\IteratorPipeline\Pipeline;
use Improved\IteratorPipeline\PipelineBuilder;
use Jasny\Entity\EntityInterface;
use function Jasny\expect_type;

/**
 * Service for converting data into entities and visa versa.
 * @todo Add trigger handlers
 */
class EntityMapper implements EntityMapperInterface
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
     * Create service with a pipeline to save entities.
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
     * Create service with a pipeline to delete entities.
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
     * Create a new entity.
     *
     * @param string $class    Entity class
     * @param mixed  ...$args  Arguments are passed to entity constructor
     * @return EntityInterface
     */
    public function create(string $class, ...$args): EntityInterface
    {
        if (!is_a($class, EntityInterface::class, true)) {
            throw new \InvalidArgumentException("$class doesn't implement EntityInterface");
        }

        return new $class(...$args);
    }

    /**
     * Turn data into entities.
     * @curry
     *
     * @param string          $class  Entity class
     * @param iterable<array> $data
     * @return PipelineBuilder|Pipeline|iterable<EntityInterface>
     */
    public function convert(string $class, iterable $data = null)
    {
        if (!is_a($class, EntityInterface::class, true)) {
            throw new \InvalidArgumentException("$class doesn't implement EntityInterface");
        }

        return (isset($data) ? Pipeline::with($data) : Pipeline::build())
            ->expectType('array')
            ->map(function (array $entry) use ($class): EntityInterface {
                return $class::__set_state($entry);
            });
    }


    /**
     * Save entities to persistent storage.
     *
     * @param iterable<EntityInterface>|EntityInterface $entities
     * @return void
     * @throws \BadMethodCallException if save pipeline is not set
     */
    public function save($entities): void
    {
        expect_type($entities, ['iterable', EntityInterface::class]);

        if (!isset($this->savePipeline)) {
            throw new \BadMethodCallException("Save pipeline is not set");
        }

        $this->savePipeline
            ->with($entities instanceof EntityInterface ? [$entities] : $entities)
            ->walk();
    }

    /**
     * Delete entities from persistent storage.
     *
     * @param iterable<EntityInterface>|EntityInterface $entities
     * @return void
     * @throws \BadMethodCallException if delete pipeline is not set
     */
    public function delete($entities): void
    {
        expect_type($entities, ['iterable', EntityInterface::class]);

        if (!isset($this->deletePipeline)) {
            throw new \BadMethodCallException("Delete pipeline is not set");
        }

        $this->deletePipeline
            ->with($entities instanceof EntityInterface ? [$entities] : $entities)
            ->walk();
    }
}
