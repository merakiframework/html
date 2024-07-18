<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Version extends Attribute
{
	/**
	 * @param string $value The value of the title attribute.
	 */
	public function __construct(string|int $value)
	{
		$this->setName('version');
		$this->setValue($value);
	}

	public function setValue(bool|int|string|\Stringable|null $value): void
	{
		if (is_string($value) && $value !== 'any') {
			throw new \InvalidArgumentException('The value of the version attribute must be an integer or the string "any".');
		}

		if (is_int($value) && ($value < 1 || $value > 8)) {
			throw new \InvalidArgumentException('The value of the version attribute must be between 1 and 8.');
		}

		$this->value = $value;
	}

	public static function any(): self
	{
		return new self('any');
	}
}
