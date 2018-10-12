<?php

namespace Jasny\EntityMapper\Tests\Pipeline;

use Jasny\Entity\DynamicEntityInterface;
use Jasny\EntityMapper\Pipeline\SavePipeline;
use Jasny\TestHelper;
use PHPUnit\Framework\MockObject\InvocationMocker;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Jasny\EntityMapper\Pipeline\SavePipeline
 */
class SavePipelineTest extends TestCase
{
    use TestHelper;

    protected function createMockEntities($count)
    {
        $entities = [];

        for ($i = 0; $i < $count; $i++) {
            $entity = $this->createMock(DynamicEntityInterface::class);
            $entity->expects($this->any())->method('toAssoc')
                ->willReturn(['name' => $i, 'age' => 20 + $i]);
            $entity->expects($this->exactly(2))->method('trigger')
                ->withConsecutive(['before-save', ['name' => $i, 'age' => 20 + $i]], ['after-save'])
                ->willReturnOnConsecutiveCalls(['name' => "N{$i}", 'age' => 20 + $i], null);

            $entities[] = $entity;
        }

        return $entities;
    }

    public function testSingle()
    {
        $expectedData = [['name' => "N0", 'age' => 20]];
        $result = [['id' => 'x0']];
        $callback = $this->createCallbackMock($this->once(), [$expectedData], $result);

        $entities = $this->createMockEntities(1);

        (new SavePipeline($callback))
            ->with($entities)
            ->walk();

        $this->assertAttributeEquals('x0', 'id', $entities[0]);
    }

    public function testMultiple()
    {
        $expectedData = [
            ['name' => "N0", 'age' => 20],
            ['name' => "N1", 'age' => 21],
            ['name' => "N2", 'age' => 22]
        ];
        $result = [
            1 => ['id' => 'x1'],
            0 => ['id' => 'x0'],
            2 => ['id' => 'x2']
        ];

        $callback = $this->createCallbackMock($this->once(), [$expectedData], $result);

        $entities = $this->createMockEntities(3);

        (new SavePipeline($callback))
            ->with($entities)
            ->walk();

        $this->assertAttributeEquals('x0', 'id', $entities[0]);
        $this->assertAttributeEquals('x1', 'id', $entities[1]);
        $this->assertAttributeEquals('x2', 'id', $entities[2]);
    }

    public function testMultipleNoChange()
    {
        $expectedData = [
            ['name' => "N0", 'age' => 20],
            ['name' => "N1", 'age' => 21],
            ['name' => "N2", 'age' => 22]
        ];

        $callback = $this->createCallbackMock($this->once(), [$expectedData]);

        $entities = $this->createMockEntities(3);

        (new SavePipeline($callback))
            ->with($entities)
            ->walk();
    }

    public function testNone()
    {
        $callback = $this->createCallbackMock($this->never());

        (new SavePipeline($callback))
            ->with([])
            ->walk();
    }


    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected all elements to be of type Jasny\Entity\EntityInterface object, string given
     */
    public function testInvalidEntities()
    {
        $callback = $this->createCallbackMock($this->never());

        (new SavePipeline($callback))
            ->with(['foo'])
            ->walk();
    }
}
