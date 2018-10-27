<?php

declare(strict_types=1);

namespace Jasny\EntityMapper;

use Improved\IteratorPipeline\Pipeline;
use Improved\IteratorPipeline\PipelineBuilder;
use Jasny\EntityMapper\Pipeline\SavePipeline;
use Jasny\EntityMapper\Pipeline\DeletePipeline;
use Jasny\Entity\Entity;
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
     * Class constructor.
     */
    public function __construct()
    {
        $this->savePipeline = new SavePipeline();
        $this->deletePipeline = new DeletePipeline();
    }

    /**
     * Create service with a custom pipeline to save entities.
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
     * Create service with a custom pipeline to delete entities.
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
     * @return Entity
     */
    public function create(string $class, ...$args): Entity
    {
        if (!is_a($class, Entity::class, true)) {
            throw new \InvalidArgumentException("$class isn't an Entity");
        }

        return new $class(...$args);
    }

    /**
     * Turn data into entities.
     * Returns (callable) pipeline builder if data is omitted.
     *
     * @param string          $class  Entity class
     * @param iterable<array> $data
     * @return PipelineBuilder|Pipeline|iterable<Entity>
     */
    public function convert(string $class, iterable $data = null)
    {
        if (!is_a($class, Entity::class, true)) {
            throw new \InvalidArgumentException("$class isn't an Entity");
        }

        return (isset($data) ? Pipeline::with($data) : Pipeline::build())
            ->expectType('array')
            ->map(function (array $entry) use ($class): Entity {
                return $class::__set_state($entry);
            });
    }


    /**
     * Save entities to persistent storage.
     *
     * @param callable                                  $persist
     * @param iterable<Entity>|Entity $entities
     * @return void
     */
    public function save(callable $persist, $entities): void
    {
        expect_type($entities, ['iterable', Entity::class]);

        $this->savePipeline
            ->unstub('persist', $persist)
            ->with($entities instanceof Entity ? [$entities] : $entities)
            ->walk();
    }

    /**
     * Delete entities from persistent storage.
     *
     * @param callable                                  $persist
     * @param iterable<Entity>|Entity $entities
     * @return void
     */
    public function delete(callable $persist, $entities): void
    {
        expect_type($entities, ['iterable', Entity::class]);

        $this->deletePipeline
            ->unstub('persist', $persist)
            ->with($entities instanceof Entity ? [$entities] : $entities)
            ->walk();
    }
}
