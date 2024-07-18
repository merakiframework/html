<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;
use Meraki\Html\Attribute\Class_;
use Meraki\Html\AttributeTest;

final class Class_Test extends AttributeTest
{
	/**
	 * @test
	 */
	public function it_exists(): void
	{
		$class = $this->createAttribute();
		$this->assertTrue($class instanceof Class_);
	}

	protected function createAttribute(): Attribute
	{
		return new Class_('hello', 'world');
	}
}
