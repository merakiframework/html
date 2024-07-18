<?php
declare(strict_types=1);

namespace Meraki\Html;

use Meraki\Html\Element;
use Meraki\TestSuite\TestCase;

final class ElementTest extends TestCase
{
	/**
	 * @test
	 */
	public function it_exists(): void
	{
		$this->assertTrue(class_exists(Element::class));
	}
}
