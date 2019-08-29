<?php

declare(strict_types=1);

namespace Jasny\Persist\ObjectMapper;

use Improved\IteratorPipeline\Pipeline;
use Improved\IteratorPipeline\PipelineBuilder;
use Jasny\Entity\DynamicEntity;
use Jasny\Entity\Entity;
use function Jasny\object_set_properties;

/**
 * Pipeline to persist entities.
 */
class SavePipeline extends PipelineBuilder
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->steps = $this
            ->expectType(Entity::class)
            ->then(function (iterable $iterable) {
                foreach ($iterable as $entity) {
                    yield $entity => $entity->toAssoc(); // key is Entity, value is data (array)
                }
            })
            ->map(function (array $data, Entity $entity) {
                return $entity->trigger('before-save', $data);
            })
            ->stub('persist')                     // key is Entity, value is modified data (auto-increment id)
            ->apply(function ($data, Entity $entity) {
                object_set_properties($entity, $data, $entity instanceof DynamicEntity);
            })
            ->keys()                                    // value is Entity
            ->apply(function (Entity $entity) {
                $entity->trigger('after-save');
            })
            ->steps;
    }

    /**
     * Get a pipeline builder where a stub is replaced.
     *
     * @param string   $name
     * @param callable $callable
     * @param mixed    ...$args
     * @return static
     */
    public function unstub(string $name, callable $callable, ...$args): PipelineBuilder
    {
        if ($name === 'persist') {
            $callable = $this->persistStep($callable);
        }

        return parent::unstub($name, $callable, $args);
    }

    /**
     * Create the step to save to persistent storage.
     *
     * @param callable $persist
     * @return callable
     */
    protected function persistStep(callable $persist): callable
    {
        return function (iterable $iterable) use ($persist): \Generator {
            $data = [];
            $entities = [];

            foreach ($iterable as $entity => $entityData) {
                $data[] = $entityData;
                $entities[] = $entity;
            }

            if (count($data) > 0) {
                $result = $persist($data);
            }

            foreach ($entities as $i => $entity) {
                yield $entity => $result[$i] ?? [];
            }
        };
    }
}
