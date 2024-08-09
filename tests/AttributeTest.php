<?php
declare(strict_types=1);

namespace Meraki\Html;

use Meraki\Html\Element;
use Meraki\TestSuite\TestCase;

abstract class AttributeTest extends TestCase
{
	/**
	 * @test
	 */
	abstract public function it_exists(): void;

	/**
	 * @test
	 */
	public function it_is_an_attribute(): void
	{
		$this->assertTrue(is_subclass_of($this->createAttribute(), Attribute::class));
	}

	/**
	 * @test
	 */
	public function an_attribute_with_a_null_value_is_cast_as_empty_string(): void
	{
		$attr = new class extends Attribute {
			public function __construct()
			{
				$this->setName('test');
				$this->setValue(null);
			}
		};

		$this->assertEquals('', (string) $attr);
	}

	abstract protected function createAttribute(): Attribute;
}
