<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Action extends Attribute
{
	public function __construct(string $value)
	{
		$this->setName('action');
		$this->setValue($value);
	}

	protected function setValue(mixed $value): void
	{
		$value = trim($value);

		if (mb_strlen($value) === 0) {
			throw new \InvalidArgumentException('The "action" attribute cannot be empty.');
		}

		parent::setValue($value);
	}
}
