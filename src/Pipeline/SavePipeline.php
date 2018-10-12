<?php

declare(strict_types=1);

namespace Jasny\EntityMapper\Pipeline;

use Improved\IteratorPipeline\PipelineBuilder;
use Jasny\Entity\DynamicEntityInterface;
use Jasny\Entity\EntityInterface;
use function Jasny\object_set_properties;

/**
 * Pipeline to persist entities.
 */
class SavePipeline extends PipelineBuilder
{
    /**
     * Class constructor.
     *
     * @param callable $save
     */
    public function __construct(callable $save)
    {
        $this->steps = $this
            ->expectType(EntityInterface::class)
            ->then(function (iterable $iterable) {
                foreach ($iterable as $entity) {
                    yield $entity => $entity->toAssoc(); // key is Entity, value is data (array)
                }
            })
            ->map(function (array $data, EntityInterface $entity) {
                return $entity->trigger('before-save', $data);
            })
            ->then($this->saveStep($save))              // key is Entity, value is modified data (auto-increment id)
            ->apply(function ($data, EntityInterface $entity) {
                object_set_properties($entity, $data, $entity instanceof DynamicEntityInterface);
            })
            ->keys()                                    // value is Entity
            ->apply(function (EntityInterface $entity) {
                $entity->trigger('after-save');
            })
            ->steps;
    }

    /**
     * Create the save step
     *
     * @param callable $save
     * @return callable
     */
    protected function saveStep(callable $save): callable
    {
        return function (iterable $iterable) use ($save): \Generator {
            $data = [];
            $entities = [];

            foreach ($iterable as $entity => $entityData) {
                $data[] = $entityData;
                $entities[] = $entity;
            }

            if (count($data) > 0) {
                $result = $save($data);
            }

            foreach ($entities as $i => $entity) {
                yield $entity => $result[$i] ?? [];
            }
        };
    }
}
