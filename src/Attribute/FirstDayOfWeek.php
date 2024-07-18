<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class FirstDayOfWeek extends Attribute
{
	public const MONDAY = 1;
	public const TUESDAY = 2;
	public const WEDNESDAY = 3;
	public const THURSDAY = 4;
	public const FRIDAY = 5;
	public const SATURDAY = 6;
	public const SUNDAY = 7;

	public function __construct(int $value)
	{
		$this->setName('first-day-of-week');

		// check for iso day number
		if ($value < 1 || $value > 7) {
			throw new \InvalidArgumentException('The first day of the week must be a number between 1 and 7.');
		}

		$this->setValue($value);
	}
}
