<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Availability extends Attribute
{
	/**
	 * @param string[] $availability An array of available datetimes,
	 * 		dates, or times, in ISO 8601 format. ISO formats cannot be mixed.
	 */
	public function __construct(array $availability)
	{
		$this->setName('availability');
		$this->setValue(implode(' ', $availability));
	}
}
