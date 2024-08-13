<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Attribute;
use Meraki\Html\Form\Field\DateTime;
use Meraki\TestSuite\TestCase;

final class DateTimeTest extends TestCase
{
	/**
	 * @test
	 */
	public function it_exists(): void
	{
		$this->assertTrue(class_exists(DateTime::class));
	}

	/**
	 * @test
	 */
	public function if_value_is_null_and_field_optional_then_field_is_valid(): void
	{
		$field = new DateTime(
			new Attribute\Name('when'),
			new Attribute\Label('When')
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
		$field = new DateTime(
			new Attribute\Name('when'),
			new Attribute\Label('When')
		);

		$field->optional();

		$field->input('');
		$this->assertFalse($field->hasErrors());
	}

	/**
	 * @test
	 */
	// public function it_renders_the_datetime_field_with_the_correct_input_type(): void
	// {
	// 	$renderer = new FieldRenderer();
	// 	$field = new Field\DateTime(
	// 		new Attribute\Name('when'),
	// 		new Attribute\Label('When')
	// 	);

	// 	$field->input('2023-10-27T11:00');

	// 	// regex looks for an attribute called "type" with a value of "datetime-local"
	// 	$this->assertRegExp('/type="datetime-local"/', $renderer->render($field));
	// }
}
