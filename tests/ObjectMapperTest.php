<?php

namespace Jasny\Persist\Tests;

use Improved\IteratorPipeline\Pipeline;
use Jasny\Persist\ObjectMapper;
use Jasny\Persist\SavePipeline;
use Jasny\Persist\DeletePipeline;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Jasny\Persist\ObjectMapper
 */
class ObjectMapperTest extends TestCase
{
    /**
     * Entity class name
     * @var string
     */
    protected $class;

    public function setUp()
    {
        $blueprint = new class() extends AbstractBasicEntity implements DynamicEntity {
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
        $mapper = new ObjectMapper();

        $entity = $mapper->create($this->class);
        $this->assertInstanceOf($this->class, $entity);
    }

    public function testCreateWithArgs()
    {
        $mapper = new ObjectMapper();

        $entity = $mapper->create($this->class, 'hello', 42);
        $this->assertInstanceOf($this->class, $entity);

        $this->assertAttributeEquals('hello', 'foo', $entity);
        $this->assertAttributeEquals(42, 'bar', $entity);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage FooBar doesn't implement Entity
     */
    public function testCreateInvalidClassName()
    {
        $mapper = new ObjectMapper();
        $mapper->create('FooBar');
    }


    public function testConvert()
    {
        $mapper = new ObjectMapper();

        $data = new \ArrayIterator([
            ['foo' => 'hello', 'bar' => 42, 'color' => 'red'],
            ['foo' => 'bye', 'bar' => 99, 'shape' => 'square']
        ]);

        $pipeline = $mapper->convert($this->class, $data);
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
     * @expectedExceptionMessage FooBar doesn't implement Entity
     */
    public function testConvertInvalidClassName()
    {
        $mapper = new ObjectMapper();
        $mapper->convert('FooBar', new \ArrayIterator());
    }


    public function testSave()
    {
        $entities = [
            $this->createMock(Entity::class),
            $this->createMock(Entity::class),
            $this->createMock(Entity::class)
        ];

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->expects($this->once())->method('walk');

        $persist = function() {};

        $savePipeline = $this->createMock(SavePipeline::class);
        $savePipeline->expects($this->once())->method('unstub')
            ->with('persist', $this->identicalTo($persist))->willReturnSelf();
        $savePipeline->expects($this->once())->method('with')
            ->with($this->identicalTo($entities))->willReturn($pipeline);

        $mapper = (new ObjectMapper())->withSave($savePipeline);

        $mapper->save($persist, $entities);
    }

    public function testSaveSingle()
    {
        $entity = $this->createMock(Entity::class);

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->expects($this->once())->method('walk');

        $persist = function() {};

        $savePipeline = $this->createMock(SavePipeline::class);
        $savePipeline->expects($this->once())->method('unstub')
            ->with('persist', $this->identicalTo($persist))->willReturnSelf();
        $savePipeline->expects($this->once())->method('with')
            ->with($this->identicalTo([$entity]))->willReturn($pipeline);

        $mapper = (new ObjectMapper())->withSave($savePipeline);

        $mapper->save($persist, $entity);
    }

    public function testWithSave()
    {
        $savePipeline1 = $this->createMock(SavePipeline::class);
        $savePipeline2 = $this->createMock(SavePipeline::class);

        $mapper = new ObjectMapper();
        $mapper1 = $mapper->withSave($savePipeline1);
        $mapper2 = $mapper1->withSave($savePipeline2);
        $mapper2a = $mapper2->withSave($savePipeline2);

        $this->assertNotSame($mapper, $mapper1);
        $this->assertAttributeSame($savePipeline1, 'savePipeline', $mapper1);

        $this->assertNotSame($mapper1, $mapper2);
        $this->assertAttributeSame($savePipeline2, 'savePipeline', $mapper2);

        $this->assertSame($mapper2, $mapper2a);
    }


    public function testDelete()
    {
        $entities = [
            $this->createMock(Entity::class),
            $this->createMock(Entity::class),
            $this->createMock(Entity::class)
        ];

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->expects($this->once())->method('walk');

        $persist = function() {};

        $deletePipeline = $this->createMock(DeletePipeline::class);
        $deletePipeline->expects($this->once())->method('unstub')
            ->with('persist', $this->identicalTo($persist))->willReturnSelf();
        $deletePipeline->expects($this->once())->method('with')
            ->with($this->identicalTo($entities))->willReturn($pipeline);

        $mapper = (new ObjectMapper())->withDelete($deletePipeline);

        $mapper->delete($persist, $entities);
    }

    public function testDeleteSingle()
    {
        $entity = $this->createMock(Entity::class);

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->expects($this->once())->method('walk');

        $persist = function() {};

        $deletePipeline = $this->createMock(DeletePipeline::class);
        $deletePipeline->expects($this->once())->method('unstub')
            ->with('persist', $this->identicalTo($persist))->willReturnSelf();
        $deletePipeline->expects($this->once())->method('with')
            ->with($this->identicalTo([$entity]))->willReturn($pipeline);

        $mapper = (new ObjectMapper())->withDelete($deletePipeline);

        $mapper->delete($persist, $entity);
    }

    public function testWithDelete()
    {
        $deletePipeline1 = $this->createMock(DeletePipeline::class);
        $deletePipeline2 = $this->createMock(DeletePipeline::class);

        $mapper = new ObjectMapper();
        $mapper1 = $mapper->withDelete($deletePipeline1);
        $mapper2 = $mapper1->withDelete($deletePipeline2);
        $mapper2a = $mapper2->withDelete($deletePipeline2);

        $this->assertNotSame($mapper, $mapper1);
        $this->assertAttributeSame($deletePipeline1, 'deletePipeline', $mapper1);

        $this->assertNotSame($mapper1, $mapper2);
        $this->assertAttributeSame($deletePipeline2, 'deletePipeline', $mapper2);

        $this->assertSame($mapper2, $mapper2a);
    }
}
