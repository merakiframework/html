<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Attribute;
use Meraki\Html\Form\Field\Date;
use Meraki\TestSuite\TestCase;

final class DateTest extends TestCase
{
	/**
	 * @test
	 */
	public function it_exists(): void
	{
		$this->assertTrue(class_exists(Date::class));
	}

	/**
	 * @test
	 */
	public function if_value_is_null_and_field_optional_then_field_is_valid(): void
	{
		$field = new Date(
			new Attribute\Name('date'),
			new Attribute\Label('date')
		);

		$field->optional();

		$field->input(null);
		$this->assertFalse($field->hasErrors());
	}

	/**
	 * @test
	 */
	public function if_value_is_empty_string_and_field_optional_then_field_is_valid(): void
	{
		$field = new Date(
			new Attribute\Name('date'),
			new Attribute\Label('Date')
		);

		$field->optional();

		$field->input('');
		$this->assertFalse($field->hasErrors());
	}
}
