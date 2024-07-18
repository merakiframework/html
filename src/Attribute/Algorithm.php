<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Algorithm extends Attribute
{
	public function __construct(string $value)
	{
		$this->setName('algorithm');
		$this->setValue($value);
	}

	protected function setValue(mixed $value): void
	{
		$value = trim($value);

		if (mb_strlen($value) === 0) {
			throw new \InvalidArgumentException('The "algorithm" attribute cannot be empty.');
		}

		parent::setValue($value);
	}
}
