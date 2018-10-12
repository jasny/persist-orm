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
     *
     * @param callable $delete
     */
    public function __construct(callable $delete)
    {
        $this->steps = $this
            ->expectType(IdentifiableEntityInterface::class)
            ->apply(function (EntityInterface $entity) {
                $entity->trigger('before-delete');
            })
            ->then($this->deleteStep($delete))
            ->apply(function (EntityInterface $entity) {
                $entity->trigger('after-delete');
            })
            ->steps;
    }

    /**
     * Create the delete step
     *
     * @param callable $delete
     * @return callable
     */
    protected function deleteStep(callable $delete): callable
    {
        return function (iterable $iterable) use ($delete): \Generator {
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
                $delete($ids);
            }

            foreach ($entities as $entity) {
                yield $entity;
            }
        };
    }
}
