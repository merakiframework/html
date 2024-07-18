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

	abstract protected function createAttribute(): Attribute;
}
