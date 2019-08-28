<?php

namespace Jasny\DB\Tests\Gateway;

use Jasny\DB\ODM\ODMInterface;
use Jasny\DB\CRUD\CRUDInterface;
use Jasny\DB\Gateway\Gateway;
use Jasny\DB\Search\SearchInterface;
use Jasny\Entity\EntityInterface;
use Jasny\Entity\Trigger\TriggerSet;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Jasny\DB\Gateway\Gateway
 */
class GatewayTest extends TestCase
{
    /** @var Gateway */
    protected $gateway;

    /** @var \stdClass */
    protected $source;

    /** @var ODMInterface|MockObject */
    protected $odm;

    /** @var CRUDInterface|MockObject */
    protected $crud;

    /** @var SearchInterface|MockObject */
    protected $search;


    public function setUp()
    {
        $this->source = new \stdClass();

        $this->odm = $this->createMock(ODMInterface::class);
        $this->crud = $this->createMock(CRUDInterface::class);
        $this->search = $this->createMock(SearchInterface::class);

        $this->gateway = new Gateway($this->source, $this->odm, $this->crud, $this->search);
    }


    public function testCreate()
    {
        $entity = $this->createMock(EntityInterface::class);
        $this->odm->expects($this->once())->method('create')->with('foo', 'bar')->willReturn($entity);
        $this->triggerSet->expects($this->once())->method('apply')->with($entity);

        $this->gateway->create('foo', 'bar');
    }

    public function testExists()
    {

    }

    public function testDelete()
    {

    }

    public function testFetchList()
    {

    }

    public function testCount()
    {

    }

    public function testFetch()
    {

    }

    public function testSave()
    {

    }

    public function testFetchAll()
    {

    }

    public function testSearch()
    {

    }

    public function testFetchPairs()
    {

    }
}
