<?php

namespace Jasny\EntityMapper\Tests;

use Improved\IteratorPipeline\Pipeline;
use Jasny\Entity\AbstractBasicEntity;
use Jasny\Entity\DynamicEntityInterface;
use Jasny\Entity\EntityInterface;
use Jasny\EntityMapper\EntityMapper;
use Jasny\EntityMapper\Pipeline\SavePipeline;
use Jasny\EntityMapper\Pipeline\DeletePipeline;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Jasny\EntityMapper\EntityMapper
 */
class EntityMapperTest extends TestCase
{
    /**
     * Entity class name
     * @var string
     */
    protected $class;

    public function setUp()
    {
        $blueprint = new class() extends AbstractBasicEntity implements DynamicEntityInterface {
            public $foo;
            public $bar;

            public function __construct($foo = null, $bar = null)
            {
                if (func_num_args() === 0) {
                    return;
                }

                $this->foo = $foo;
                $this->bar = $bar;
            }
        };

        $this->class = get_class($blueprint);
    }


    public function testCreate()
    {
        $entityMapper = new EntityMapper();

        $entity = $entityMapper->create($this->class);
        $this->assertInstanceOf($this->class, $entity);
    }

    public function testCreateWithArgs()
    {
        $entityMapper = new EntityMapper();

        $entity = $entityMapper->create($this->class, 'hello', 42);
        $this->assertInstanceOf($this->class, $entity);

        $this->assertAttributeEquals('hello', 'foo', $entity);
        $this->assertAttributeEquals(42, 'bar', $entity);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage FooBar doesn't implement EntityInterface
     */
    public function testCreateInvalidClassName()
    {
        $entityMapper = new EntityMapper();
        $entityMapper->create('FooBar');
    }


    public function testConvert()
    {
        $entityMapper = new EntityMapper();

        $data = new \ArrayIterator([
            ['foo' => 'hello', 'bar' => 42, 'color' => 'red'],
            ['foo' => 'bye', 'bar' => 99, 'shape' => 'square']
        ]);

        $pipeline = $entityMapper->convert($this->class, $data);
        $this->assertInstanceOf(Pipeline::class, $pipeline);

        $entities = $pipeline->toArray();
        $this->assertCount(2, $entities);

        $this->assertInstanceOf($this->class, $entities[0]);
        $this->assertAttributeEquals('hello', 'foo', $entities[0]);
        $this->assertAttributeEquals(42, 'bar', $entities[0]);
        $this->assertAttributeEquals('red', 'color', $entities[0]);

        $this->assertInstanceOf($this->class, $entities[1]);
        $this->assertAttributeEquals('bye', 'foo', $entities[1]);
        $this->assertAttributeEquals(99, 'bar', $entities[1]);
        $this->assertAttributeEquals('square', 'shape', $entities[1]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage FooBar doesn't implement EntityInterface
     */
    public function testConvertInvalidClassName()
    {
        $entityMapper = new EntityMapper();
        $entityMapper->convert('FooBar', new \ArrayIterator());
    }


    public function testSave()
    {
        $entities = [
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class)
        ];

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->expects($this->once())->method('walk');

        $savePipeline = $this->createMock(SavePipeline::class);
        $savePipeline->expects($this->once())->method('with')
            ->with($this->identicalTo($entities))->willReturn($pipeline);

        $entityMapper = (new EntityMapper())->withSave($savePipeline);

        $entityMapper->save($entities);
    }

    public function testSaveSingle()
    {
        $entity = $this->createMock(EntityInterface::class);

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->expects($this->once())->method('walk');

        $savePipeline = $this->createMock(SavePipeline::class);
        $savePipeline->expects($this->once())->method('with')
            ->with($this->identicalTo([$entity]))->willReturn($pipeline);

        $entityMapper = (new EntityMapper())->withSave($savePipeline);

        $entityMapper->save($entity);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Save pipeline is not set
     */
    public function testSaveNotSet()
    {
        $entity = $this->createMock(EntityInterface::class);

        $entityMapper = new EntityMapper();

        $entityMapper->save($entity);
    }

    public function testWithSave()
    {
        $savePipeline1 = $this->createMock(SavePipeline::class);
        $savePipeline2 = $this->createMock(SavePipeline::class);

        $entityMapper = new EntityMapper();
        $entityMapper1 = $entityMapper->withSave($savePipeline1);
        $entityMapper2 = $entityMapper1->withSave($savePipeline2);
        $entityMapper2a = $entityMapper2->withSave($savePipeline2);

        $this->assertNotSame($entityMapper, $entityMapper1);
        $this->assertAttributeSame($savePipeline1, 'savePipeline', $entityMapper1);

        $this->assertNotSame($entityMapper1, $entityMapper2);
        $this->assertAttributeSame($savePipeline2, 'savePipeline', $entityMapper2);

        $this->assertSame($entityMapper2, $entityMapper2a);
    }


    public function testDelete()
    {
        $entities = [
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class)
        ];

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->expects($this->once())->method('walk');

        $deletePipeline = $this->createMock(DeletePipeline::class);
        $deletePipeline->expects($this->once())->method('with')
            ->with($this->identicalTo($entities))->willReturn($pipeline);

        $entityMapper = (new EntityMapper())->withDelete($deletePipeline);

        $entityMapper->delete($entities);
    }

    public function testDeleteSingle()
    {
        $entity = $this->createMock(EntityInterface::class);

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->expects($this->once())->method('walk');

        $deletePipeline = $this->createMock(DeletePipeline::class);
        $deletePipeline->expects($this->once())->method('with')
            ->with($this->identicalTo([$entity]))->willReturn($pipeline);

        $entityMapper = (new EntityMapper())->withDelete($deletePipeline);

        $entityMapper->delete($entity);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Delete pipeline is not set
     */
    public function testDeleteNotSet()
    {
        $entity = $this->createMock(EntityInterface::class);

        $entityMapper = new EntityMapper();

        $entityMapper->delete($entity);
    }

    public function testWithDelete()
    {
        $deletePipeline1 = $this->createMock(DeletePipeline::class);
        $deletePipeline2 = $this->createMock(DeletePipeline::class);

        $entityMapper = new EntityMapper();
        $entityMapper1 = $entityMapper->withDelete($deletePipeline1);
        $entityMapper2 = $entityMapper1->withDelete($deletePipeline2);
        $entityMapper2a = $entityMapper2->withDelete($deletePipeline2);

        $this->assertNotSame($entityMapper, $entityMapper1);
        $this->assertAttributeSame($deletePipeline1, 'deletePipeline', $entityMapper1);

        $this->assertNotSame($entityMapper1, $entityMapper2);
        $this->assertAttributeSame($deletePipeline2, 'deletePipeline', $entityMapper2);

        $this->assertSame($entityMapper2, $entityMapper2a);
    }
}
