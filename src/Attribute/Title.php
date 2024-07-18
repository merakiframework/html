<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Title extends Attribute
{
	/**
	 * @param string $value The value of the title attribute.
	 */
	public function __construct(string $value)
	{
		parent::__construct('title', $value);
	}
}
