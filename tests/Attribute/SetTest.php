<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;
use Meraki\Html\Exception\AttributesNotAllowed;
use Meraki\TestSuite\TestCase;

final class SetTest extends TestCase
{
	/**
	 * @test
	 */
	public function can_be_created_with_no_attributes(): void
	{
		$set = Set::allowAny();

		$this->assertCount(0, $set);
		$this->assertEmpty($set);
	}

	/**
	 * @test
	 */
	public function can_be_created_with_only_global_attributes_allowed(): void
	{
		$globalAttributes = self::globalAttributes();
		$set = Set::useGlobal();

		$this->assertTrue($set->allowed(...$globalAttributes));
		$this->assertFalse($set->allowed(Attribute\Action::class));
	}

	/**
	 * @test
	 */
	public function can_be_created_with_global_and_additional_attributes_allowed(): void
	{
		$globalAttributes = self::globalAttributes();
		$set = Set::useGlobal(Attribute\Action::class);

		$this->assertTrue($set->allowed(...$globalAttributes));
		$this->assertTrue($set->allowed(Attribute\Action::class));
	}

	/**
	 * @test
	 */
	public function only_allowed_attributes_are_added(): void
	{
		$set = Set::use(Attribute\Accesskey::class);
		$exception = new AttributesNotAllowed([Attribute\Class_::class]);

		$this->assertThrows($exception, fn() => $set->add(new Attribute\Class_()));
		$this->assertTrue($set->allowed(Attribute\Accesskey::class));
		$this->assertFalse($set->allowed(Attribute\Class_::class));
	}

	/**
	 * @test
	 */
	public function can_get_index_of_an_attribute_by_its_name(): void
	{
		$set = new Set();

		$set->add(
			new Attribute\Class_('foo'),
			new Attribute('popover', ''),
			new Attribute\Style(['color' => 'red']),
		);

		$this->assertEquals(0, $set->indexOf('class'));
		$this->assertEquals(1, $set->indexOf('popover'));
		$this->assertEquals(2, $set->indexOf('style'));
	}

	/**
	 * @test
	 */
	public function can_get_index_of_attribute_by_its_fqcn(): void
	{
		$set = new Set();

		$set->add(
			new Attribute\Class_('foo'),
			new Attribute('popover', ''),
			new Attribute\Style(['color' => 'red']),
		);

		$this->assertEquals(0, $set->indexOf(Attribute\Class_::class));
		$this->assertEquals(2, $set->indexOf(Attribute\Style::class));
	}

	/**
	 * @test
	 */
	public function trying_to_get_index_of_superclass_by_fqcn_throws_error(): void
	{
		$exception = new \InvalidArgumentException('Cannot check for the "Meraki\\Html\\Attribute" superclass unless passed as instance.');
		$set = new Set();

		$set->add(
			new Attribute\Class_('foo'),
			new Attribute('popover', ''),
			new Attribute\Style(['color' => 'red']),
		);

		$this->assertThrows($exception, fn() => $set->indexOf(Attribute::class));
	}

	/**
	 * @test
	 */
	public function can_get_index_of_attribute_by_its_instance(): void
	{
		$class = new Attribute\Class_('foo');
		$popover = new Attribute('popover', '');
		$style = new Attribute\Style(['color' => 'red']);
		$set = new Set();

		$set->add($class, $popover, $style);

		$this->assertEquals(0, $set->indexOf($class));
		$this->assertEquals(1, $set->indexOf($popover));
		$this->assertEquals(2, $set->indexOf($style));
	}

	/**
	 * @test
	 */
	public function can_create_a_default_attribute_using_factory_if_not_found(): void
	{
		$set = new Set();

		$this->assertNull($set->find(Attribute\Class_::class));

		$class = $set->findOrCreate(Attribute\Class_::class, fn() => new Attribute\Class_('foo'));

		$this->assertNotNull($class);
	}

	/**
	 * @test
	 */
	public function a_default_factory_is_called_if_trying_to_find_an_attribute_that_does_not_exist(): void
	{
		$set = new Set();

		$this->assertNull($set->find(Attribute\Class_::class));

		$class = $set->findOrCreate(Attribute\Class_::class);

		$this->assertNotNull($class);
	}

	/**
	 * @test
	 */
	public function arguments_can_be_passed_to_the_default_factory(): void
	{
		$set = new Set();

		$this->assertNull($set->find(Attribute\Class_::class));

		$class = $set->findOrCreate(Attribute\Class_::class, 'foo', 'bar');

		$this->assertNotNull($class);
		$this->assertTrue($class->contains('foo'));
		$this->assertTrue($class->contains('bar'));
		$this->assertEquals('foo bar', $class->value);
	}

	private static function globalAttributes(): array
	{
		return [
			Attribute\Accesskey::class,
			Attribute\Class_::class,
			Attribute\Contenteditable::class,
			Attribute\Hidden::class,
			Attribute\Id::class,
			Attribute\Style::class,
			Attribute\Title::class,
		];
	}
}
