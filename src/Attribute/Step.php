<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Step extends Attribute
{
	public function __construct(string $value)
	{
		$this->setName('step');
		$this->setValue($value);
	}

	public static function inIncrementsOf(float|int|string $increment): self
	{
		if (is_int($increment) || is_float($increment)) {
			$increment = (string)$increment;
		}

		return new self($increment);
	}
}
