<?php

namespace Jasny\EntityMapper\Tests\Pipeline;

use Jasny\Entity\IdentifiableEntityInterface;
use Jasny\EntityMapper\Pipeline\DeletePipeline;
use Jasny\TestHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Jasny\EntityMapper\Pipeline\DeletePipeline
 */
class DeletePipelineTest extends TestCase
{
    use TestHelper;

    protected function createMockEntities($count)
    {
        $entities = [];

        for ($i = 0; $i < $count; $i++) {
            $entity = $this->createMock(IdentifiableEntityInterface::class);
            $entity->expects($this->any())->method('getId')->willReturn("x{$i}");
            $entity->expects($this->exactly(2))->method('trigger')
                ->withConsecutive(['before-delete'], ['after-delete']);

            $entities[] = $entity;
        }

        return $entities;
    }

    public function testSingle()
    {
        $expectedIds = ['x0'];
        $callback = $this->createCallbackMock($this->once(), [$expectedIds]);

        $entities = $this->createMockEntities(1);

        (new DeletePipeline($callback))
            ->with($entities)
            ->walk();
    }

    public function testMultiple()
    {
        $expectedIds = ['x0', 'x1', 'x2'];
        $callback = $this->createCallbackMock($this->once(), [$expectedIds]);

        $entities = $this->createMockEntities(3);

        (new DeletePipeline($callback))
            ->with($entities)
            ->walk();
    }

    public function testNone()
    {
        $callback = $this->createCallbackMock($this->never());

        (new DeletePipeline($callback))
            ->with([])
            ->walk();
    }


    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected all elements to be of type Jasny\Entity\IdentifiableEntityInterface object,
     *   string given
     */
    public function testInvalidEntities()
    {
        $callback = $this->createCallbackMock($this->never());

        (new DeletePipeline($callback))
            ->with(['foo'])
            ->walk();
    }
}
