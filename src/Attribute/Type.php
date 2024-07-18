<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Type extends Attribute
{
	public function __construct(string $value)
	{
		$this->setName('type');
		$this->setValue($value);
	}

	public function is(string $type): bool
	{
		return $this->value === strtolower($type);
	}

	protected function setValue(mixed $value): void
	{
		$value = trim($value);

		if (mb_strlen($value) === 0) {
			throw new \InvalidArgumentException('The "type" attribute cannot be empty.');
		}

		parent::setValue(strtolower($value));
	}
}
