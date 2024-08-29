<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;
use Meraki\TestSuite\TestCase;

/**
 * @covers Field\Set
 */
final class SetTest extends TestCase
{
	/**
	 * @test
	 */
	public function it_exists(): void
	{
		$this->assertTrue(class_exists(Field\Set::class));
	}

	/**
	 * @test
	 */
	public function can_retrieve_a_field_by_its_name(): void
	{
		$name = 'username';
		$field = new Field\Text(new Attribute\Name($name), new Attribute\Label('Username'));
		$set = new Field\Set($field);

		$this->assertSame($field, $set->findByName($name));
	}
}
