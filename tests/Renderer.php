<?php
declare(strict_types=1);

namespace Meraki\Html;

use Meraki\Html\Renderer;
use Meraki\TestSuite\TestCase;

final class RendererTest extends TestCase
{
	/**
	 * @test
	 */
	public function it_exists(): void
	{
		$this->assertTrue(class_exists(Renderer::class));
	}
}
