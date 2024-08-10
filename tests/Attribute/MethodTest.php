<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;
use Meraki\Html\Attribute\Method;
use Meraki\Html\AttributeTest;

final class MethodTest extends AttributeTest
{
	/**
	 * @test
	 */
	public function it_exists(): void
	{
		$method = $this->createAttribute();
		$this->assertTrue($method instanceof Method);
	}

	/**
	 * @test
	 */
	public function a_post_method_can_be_verified(): void
	{
		$method = new Method('post');

		$this->assertTrue($method->isPost());
		$this->assertFalse($method->isPut());
		$this->assertFalse($method->isDelete());
		$this->assertFalse($method->isPatch());
	}

	/**
	 * @test
	 */
	public function a_put_method_can_be_verified(): void
	{
		$method = new Method('put');

		$this->assertFalse($method->isPost());
		$this->assertTrue($method->isPut());
		$this->assertFalse($method->isDelete());
		$this->assertFalse($method->isPatch());
	}

	/**
	 * @test
	 */
	public function a_delete_method_can_be_verified(): void
	{
		$method = new Method('delete');

		$this->assertFalse($method->isPost());
		$this->assertFalse($method->isPut());
		$this->assertTrue($method->isDelete());
		$this->assertFalse($method->isPatch());
	}

	/**
	 * @test
	 */
	public function a_patch_method_can_be_verified(): void
	{
		$method = new Method('patch');

		$this->assertFalse($method->isPost());
		$this->assertFalse($method->isPut());
		$this->assertFalse($method->isDelete());
		$this->assertTrue($method->isPatch());
	}

	/**
	 * @test
	 */
	public function it_cannot_have_an_empty_value(): void
	{
		$expectedException = new \InvalidArgumentException('The "method" attribute cannot be empty.');

		$this->assertThrows($expectedException, fn() => new Method(''));
	}

	protected function createAttribute(): Attribute
	{
		return new Method('post');
	}
}
