<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Value extends Attribute
{
	public function __construct(bool|int|string|\Stringable|null $value)
	{
		$this->setName('value');
		$this->setValue($value);
	}

	/**
	 * Check if the value is provided.
	 *
	 * A value is considered provided if it is not null and not empty.
	 */
	public function provided(): bool
	{
		return !$this->isNull() && !$this->isEmpty();
	}

	/**
	 * Check if the value is null.
	 */
	public function isNull(): bool
	{
		return $this->value === null;
	}

	/**
	 * Check if the value is considered empty.
	 *
	 * Only arrays and strings can be considered empty. All other types will return false.
	 */
	public function isEmpty(): bool
	{
		if (is_array($this->value)) {
			return count($this->value) === 0;
		}

		if (is_string($this->value)) {
			return $this->value === '';
		}

		return false;
	}
}
