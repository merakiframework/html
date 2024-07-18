<?php
declare(strict_types=1);

namespace Meraki\Html;

use Meraki\Html\Element;
use Meraki\Html\Form;
use Meraki\TestSuite\TestCase;

final class FormTest extends TestCase
{
	/**
	 * @test
	 */
	public function it_exists(): void
	{
		$this->assertTrue(class_exists(Form::class));
	}

	/**
	 * @test
	 */
	public function it_is_an_element(): void
	{
		$form = new Form('login_form', '/login');

		$this->assertInstanceOf(Element::class, $form);
	}
}
