<?php

namespace Jasny\Persist\Tests\ObjectMapper;

use Jasny\Entity\IdentifiableEntity;
use Jasny\EntityMapper\Pipeline\DeletePipeline;
use Jasny\TestHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Jasny\Persist\ObjectMapper\DeletePipeline
 */
class DeletePipelineTest extends TestCase
{
    use TestHelper;

    protected function createMockEntities($count)
    {
        $entities = [];

        for ($i = 0; $i < $count; $i++) {
            $entity = $this->createMock(IdentifiableEntity::class);
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
        $persist = $this->createCallbackMock($this->once(), [$expectedIds]);

        $entities = $this->createMockEntities(1);

        (new DeletePipeline())
            ->unstub('persist', $persist)
            ->with($entities)
            ->walk();
    }

    public function testMultiple()
    {
        $expectedIds = ['x0', 'x1', 'x2'];
        $persist = $this->createCallbackMock($this->once(), [$expectedIds]);

        $entities = $this->createMockEntities(3);

        (new DeletePipeline())
            ->unstub('persist', $persist)
            ->with($entities)
            ->walk();
    }

    public function testNone()
    {
        $persist = $this->createCallbackMock($this->never());

        (new DeletePipeline())
            ->unstub('persist', $persist)
            ->with([])
            ->walk();
    }

    public function testNoPersist()
    {
        $entities = $this->createMockEntities(3);

        (new DeletePipeline())
            ->with($entities)
            ->walk();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected all elements to be of type Jasny\Entity\IdentifiableEntity object,
     *   string given
     */
    public function testInvalidEntities()
    {
        (new DeletePipeline())
            ->with(['foo'])
            ->walk();
    }
}
