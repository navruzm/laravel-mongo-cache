<?php

use Mockery as m;
use MongoCache\MongoStore;

class CacheMongoStoreTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testNullIsReturnedWhenItemNotFound()
	{
		$store = $this->getStore();
		$store->getConnection()->collection = m::mock('StdClass');
		$store->getConnection()->collection->shouldReceive('findOne')->once()->with(array('key' => 'prefixfoo'))->andReturn(null);

		$this->assertNull($store->get('foo'));
	}


	public function testNullIsReturnedAndItemDeletedWhenItemIsExpired()
	{
		$store = $this->getMock('MongoCache\MongoStore', array('forget'), $this->getMocks());
		$store->getConnection()->collection = m::mock('StdClass');
		$store->getConnection()->collection->shouldReceive('findOne')->once()->with(array('key' => 'prefixfoo'))->andReturn(array('expiration' => new MongoDate(1)));
		$store->expects($this->once())->method('forget')->with($this->equalTo('foo'))->will($this->returnValue(null));

		$this->assertNull($store->get('foo'));
	}


	public function testDecryptedValueIsReturnedWhenItemIsValid()
	{
		$store = $this->getStore();
		$store->getConnection()->collection = m::mock('StdClass');
		$store->getConnection()->collection->shouldReceive('findOne')->once()->with(array('key' => 'prefixfoo'))->andReturn(array('value' => 'bar', 'expiration' => new MongoDate(999999999999999)));
		$store->getEncrypter()->shouldReceive('decrypt')->once()->with('bar')->andReturn('bar');

		$this->assertEquals('bar', $store->get('foo'));
	}

	public function testEncryptedValueIsInsertedWhenFindOneIsNull()
	{
		$store = $this->getMock('MongoCache\MongoStore', array('getTime'), $this->getMocks());
		$collection = m::mock('StdClass');
		$store->getConnection()->collection = m::mock('StdClass');
		$store->getConnection()->collection->shouldReceive('findOne')->once()->with(array('key' => 'prefixfoo'))->andReturn(null);
		$store->getEncrypter()->shouldReceive('encrypt')->once()->with('bar')->andReturn('bar');
		$store->expects($this->once())->method('getTime')->will($this->returnValue(1));
		$store->getConnection()->collection->shouldReceive('insert')->once()->with(array('key' => 'prefixfoo', 'value' => 'bar', 'expiration' => new MongoDate(61)));

		$store->put('foo', 'bar', 1);
	}


	public function testEncryptedValueIsUpdatedWhenFindOneHasResult()
	{
		$store = $this->getMock('MongoCache\MongoStore', array('getTime'), $this->getMocks());
		$collection = m::mock('StdClass');
		$store->getConnection()->collection = m::mock('StdClass');
		$store->getConnection()->collection->shouldReceive('findOne')->once()->with(array('key' => 'prefixfoo'))->andReturn(array());
		$store->getEncrypter()->shouldReceive('encrypt')->once()->with('bar')->andReturn('bar');
		$store->expects($this->once())->method('getTime')->will($this->returnValue(1));
		$store->getConnection()->collection->shouldReceive('update')->once()->with(array('key' => 'prefixfoo'), array('$set' => array('value' => 'bar', 'expiration' => new MongoDate(61))));

		$store->put('foo', 'bar', 1);
	}


	public function testForeverCallsStoreItemWithReallyLongTime()
	{
		$store = $this->getMock('MongoCache\MongoStore', array('put'), $this->getMocks());
		$store->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(5256000));
		$store->forever('foo', 'bar');
	}


	public function testItemsMayBeRemovedFromCache()
	{
		$store = $this->getStore();
		$store->getConnection()->collection = m::mock('StdClass');
		$store->getConnection()->collection->shouldReceive('remove')->once()->with(array('key' => 'prefixfoo'));

		$store->forget('foo');
	}


	public function testItemsMayBeFlushedFromCache()
	{
		$store = $this->getStore();
		$store->getConnection()->collection = m::mock('StdClass');
		$store->getConnection()->collection->shouldReceive('drop')->once();

		$store->flush();
	}


	protected function getStore()
	{
		return new MongoStore(m::mock('LMongo\Connection'), m::mock('Illuminate\Encryption\Encrypter'), 'collection', 'prefix');
	}


	protected function getMocks()
	{
		return array(m::mock('LMongo\Connection'), m::mock('Illuminate\Encryption\Encrypter'), 'collection', 'prefix');
	}

}