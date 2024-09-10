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
	public function attributes_that_are_comparable_by_name_only_are_added(): void
	{
		$set = new Set();

		$set->add(
			new Attribute\Data('foo1', 'bar1'),
			new Attribute\Data('foo2', 'bar2'),
		);

		$this->assertEquals(0, $set->indexOf('data-foo1'));
		$this->assertEquals(1, $set->indexOf('data-foo2'));
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
		$exception = new \InvalidArgumentException("The 'Meraki\Html\Attribute' attribute cannot be manipulated using the FQCN.");
		$set = new Set();

		$set->add(
			new Attribute\Class_('foo'),
			new Attribute('popover', ''),
			new Attribute\Style(['color' => 'red']),
		);

		$this->assertNull($set->indexOf(Attribute::class));
		// $this->assertThrows($exception, fn() => $set->indexOf(Attribute::class));
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

	/**
	 * @test
	 */
	public function if_attribute_subclass_not_found_superclass_is_called_when_creating_attribute(): void
	{
		$fqcn = 'Meraki\\Html\\Attribute\\Popover';
		$set = new Set();

		$this->assertNull($set->find($fqcn));

		$popover = $set->findOrCreate($fqcn, 'popover', '');

		$this->assertNotNull($popover);
		$this->assertInstanceOf(Attribute::class, $popover);
		$this->assertEquals('popover', $popover->name);
		$this->assertEquals('', $popover->value);
	}

	/**
	 * @test
	 */
	public function throws_error_when_cannot_create_attribute(): void
	{
		$exception = new \RuntimeException('Could not create factory for attribute "popover".');
		$set = new Set();

		$this->assertThrows($exception, fn() => $set->findOrCreate('popover'));
	}

	/**
	 * @test
	 */
	public function can_remove_an_attribute_by_superclass_instance(): void
	{
		$attrs = new Set();
		$attrs->add(new Attribute('popover', ''));

		$this->assertTrue($attrs->contains('popover'));

		$attrs->remove(new Attribute('popover', 'foo'));

		$this->assertFalse($attrs->contains('popover'));
	}

	/**
	 * @test
	 */
	public function can_set_attribute_using_superclass_if_not_already_exists(): void
	{
		$attrs = new Set();

		$attrs->set(new Attribute('popover', ''));

		$this->assertTrue($attrs->contains('popover'));
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
