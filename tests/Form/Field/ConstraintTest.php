<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field\Constraints;
use Meraki\TestSuite\TestCase;

final class ConstraintTest extends TestCase
{
	/**
	 * @test
	 */
	public function it_can_be_implemented(): void
	{
		$this->assertTrue(interface_exists(Constraint::class));
	}
}
