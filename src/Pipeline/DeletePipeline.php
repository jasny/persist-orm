<?php

declare(strict_types=1);

namespace Jasny\EntityMapper\Pipeline;

use Improved\IteratorPipeline\PipelineBuilder;
use Jasny\Entity\EntityInterface;
use Jasny\Entity\IdentifiableEntityInterface;

/**
 * Pipeline to delete entities.
 */
class DeletePipeline extends PipelineBuilder
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->steps = $this
            ->expectType(IdentifiableEntityInterface::class)
            ->apply(function (EntityInterface $entity) {
                $entity->trigger('before-delete');
            })
            ->stub('persist')
            ->apply(function (EntityInterface $entity) {
                $entity->trigger('after-delete');
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
     * Create the step to delete from persistent storage
     *
     * @param callable $persist
     * @return callable
     */
    protected function persistStep(callable $persist): callable
    {
        return function (iterable $iterable) use ($persist): \Generator {
            $ids = [];
            $entities = [];

            foreach ($iterable as $entity) {
                $id = $entity->getId();

                if ($id !== null) {
                    $ids[] = $id;
                    $entities[] = $entity;
                }
            }

            if (count($ids) > 0) {
                $persist($ids);
            }

            foreach ($entities as $entity) {
                yield $entity;
            }
        };
    }
}
