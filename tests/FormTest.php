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
		$attributes = new Attribute\Set();
		$attributes->set(new Attribute\Action('/login'));

		$form = new Form($attributes);

		$this->assertInstanceOf(Element::class, $form);
		$this->assertInstanceOf(Form::class, $form);
	}

	/**
	 * @test
	 */
	public function can_put_a_form_to_the_url(): void
	{
		$form = Form::putTo('/login');

		$this->assertTrue($form->attributes->contains(new Attribute\Action('/login')));
		$this->assertTrue($form->attributes->contains(Attribute\Method::class));
		$this->assertTrue($form->attributes->get(Attribute\Method::class)->equals(new Attribute\Method('put')));
	}

	/**
	 * @test
	 */
	public function can_post_a_form_to_the_url(): void
	{
		$form = Form::postTo('/login');

		$this->assertTrue($form->attributes->contains(new Attribute\Action('/login')));
		$this->assertTrue($form->attributes->contains(Attribute\Method::class));
		$this->assertTrue($form->attributes->get(Attribute\Method::class)->equals(new Attribute\Method('post')));
	}
}
