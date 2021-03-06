<?php namespace Sofa\Eloquence\Tests;

use Mockery as m;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

class EloquenceTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		EloquenceStub::clearHooks();
	}

	public function tearDown()
	{
		m::close();
	}

	/**
	 * @test
	 * @covers \Sofa\Eloquence\Eloquence::newEloquentBuilder
	 */
	public function it_uses_custom_builder()
	{
		$query   = m::mock('\Illuminate\Database\Query\Builder');
		$builder = (new EloquenceStub)->newEloquentBuilder($query);

		$this->assertInstanceOf('\Sofa\Eloquence\Builder', $builder);
	}

	/**
	 * @test
	 * @covers \Sofa\Eloquence\Eloquence::hasColumn
	 * @covers \Sofa\Eloquence\Eloquence::getColumnListing
	 * @covers \Sofa\Eloquence\Eloquence::loadColumnListing
	 */
	public function it_loads_and_checks_the_column_listing()
	{
		$schema = m::mock('StdClass');
		$schema->shouldReceive('getColumnListing')->once()->andReturn(['foo', 'bar', 'baz']);

		$connection = m::mock('StdClass');
		$connection->shouldReceive('getSchemaBuilder')->once()->andReturn($schema);

		$resolver = m::mock('\Illuminate\Database\ConnectionResolverInterface');
		$resolver->shouldReceive('connection')->once()->andReturn($connection);

		EloquenceStub::setConnectionResolver($resolver);

		$model = new EloquenceStub;

		$this->assertTrue($model->hasColumn('foo'));
		$this->assertFalse($model->hasColumn('wrong'));
		$this->assertEquals(['foo', 'bar', 'baz'], $model->getColumnListing());
	}

	/**
	 * @test
	 * @covers \Sofa\Eloquence\Eloquence::hasHook
	 */
	public function it_finds_registered_hooks()
	{
		EloquenceStub::hook('__isset', '__issetExtensionStub');

		$model = new EloquenceStub;

		$this->assertTrue($model->hasHook('__isset'));
	}

	/**
	 * @test
	 * @covers \Sofa\Eloquence\Eloquence::hook
	 * @covers \Sofa\Eloquence\Eloquence::wrapHook
	 * @covers \Sofa\Eloquence\Eloquence::unwrapHooks
	 */
	public function it_registers_and_call_hooks_on_eloquent_methods()
	{
		EloquenceStub::hook('__isset', '__issetExtensionStub');

		$model = new EloquenceStub;

		$this->assertFalse(isset($model->foo));

		$model->foo = 1;
		$this->assertFalse(isset($model->foo));
	}
}

class EloquenceStub extends Model {

	use Eloquence, ExtensionStub;

	public static function clearHooks()
	{
		static::$wrappedHooks = [];
	}
}

trait ExtensionStub {

	public function __issetExtensionStub()
	{
		return function () {
			return false;
		};
	}
}
