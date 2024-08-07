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
	public function can_check_if_an_attribute_is_contained_in_the_set_by_class(): void
	{
		$set = new Set(Attribute\Name::class);
		$set->add(new Attribute\Name('username'));

		$this->assertTrue($set->contains(Attribute\Name::class));
	}

	/**
	 * @test
	 */
	public function can_check_if_an_attribute_is_contained_in_the_set_by_instance(): void
	{
		$name = new Attribute\Name('username');
		$set = new Set(Attribute\Name::class);
		$set->add($name);

		$this->assertTrue($set->contains($name));
		$this->assertFalse($set->contains(new Attribute\Name('username2')));
	}

	/**
	 * @test
	 */
	public function can_find_an_attribute_by_its_class(): void
	{
		$set = new Set(Attribute\Name::class);
		$set->add(new Attribute\Name('username'));

		$this->assertNotNull($set->find(Attribute\Name::class));
	}

	/**
	 * @test
	 */
	public function can_find_an_attribute_by_its_instance(): void
	{
		$name = new Attribute\Name('username');
		$set = new Set(Attribute\Name::class);
		$set->add($name);

		$this->assertEquals($name, $set->find($name));
		$this->assertNull($set->find(new Attribute\Name('username2')));
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
