<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Data extends Attribute
{
	/**
	 * Constructor.
	 *
	 * @param string $name The name of the data attribute, excluding the "data-" prefix.
	 * @param string $value The value of the data attribute.
	 */
	public function __construct(string $name, string $value)
	{
		$this->setName('data-' . $name);
		$this->setValue($value);
	}
}
