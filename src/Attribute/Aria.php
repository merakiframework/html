<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Aria extends Attribute
{
	/**
	 * Constructor.
	 *
	 * @param string $name The name of the aria attribute, excluding the "aria-" prefix.
	 * @param string $value The value of the aria attribute.
	 */
	public function __construct(string $name, string $value)
	{
		$this->setName('aria-' . $name);
		$this->setValue($value);
	}
}
