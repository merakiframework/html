<?php
declare(strict_types=1);

namespace Meraki\Html\Form;

use Meraki\Html\Form\Field\Schema;
use Meraki\Html\Form\Field\Text;
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
		$schema = Schema::fromArray([
			'type' => 'text',
			'name' => 'aa__11',
			'label' => 'Username',
			'required' => true,
			'min' => 3,
			'max' => 20,
		]);

		$field = Field::createFromSchema($schema);

		$this->assertEquals($schema->createField(), $field);
	}
}
