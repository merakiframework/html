<?php
declare(strict_types=1);

namespace Meraki\Html\Form;

use Meraki\Html\Attribute;
use Meraki\Html\Form\Field;
use Meraki\TestSuite\TestCase;

final class FieldTest extends TestCase
{
	/**
	 * @test
	 */
	public function it_exists(): void
	{
		$this->assertTrue(class_exists(Field::class));
	}

	/**
	 * @test
	 */
	// public function it_has_a_type(): void
	// {
	// 	$field = new Text('username');

	// 	$this->assertEquals('text', $field->getType());
	// }

	/**
	 * @test
	 */
	public function it_can_create_a_field_from_a_schema(): void
	{
		$field = Field::createFromSchema([
			'type' => Field\Text::class,
			'name' => 'aa__11',
			'label' => 'Username',
			'required' => true,
			'min' => 3,
			'max' => 20,
		]);

		$this->assertInstanceOf(Field::class, $field);
		$this->assertInstanceOf(Field\Text::class, $field);
		$this->assertTrue($field->attributes->contains(new Attribute\Name('aa__11')));
		$this->assertTrue($field->attributes->contains(new Attribute\Label('Username')));
		$this->assertTrue($field->attributes->contains(new Attribute\Required()));
		$this->assertTrue($field->attributes->contains(new Attribute\Min(3)));
		$this->assertTrue($field->attributes->contains(new Attribute\Max(20)));
	}
}
