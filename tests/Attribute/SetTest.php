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
