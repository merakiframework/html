<?php
declare(strict_types=1);

namespace Meraki\Html;
use Meraki\Html\Attribute\Boolean;

class Attribute
{
	public string $name;
	public int|\Stringable|bool|string|null $value;

	public function __construct(string $name, int|\Stringable|bool|string|null $value)
	{
		$this->setName($name);
		$this->setValue($value);
	}

	public function canBeEmpty(): bool
	{
		return false;
	}

	public function hasNameOf(string $name): bool
	{
		return strcasecmp($this->name, $name) === 0;
	}

	public function equals(Attribute $other): bool
	{
		return $other instanceof static		// check if other attribute is instance of the subclass
			&& $this->name === $other->name
			&& $this->value === $other->value;
	}

	protected function setName(string $name): void
	{
		if (mb_strlen($name) === 0) {
			throw new \InvalidArgumentException('The name of an attribute cannot be empty.');
		}

		$this->name = $name;
	}

	protected function setValue(int|\Stringable|bool|string|null $value): void
	{
		$this->value = $value;
	}

	public function __toString(): string
	{
		if ($this instanceof Attribute\Boolean) {
			return $this->name;
		}

		return $this->name . '="' . htmlspecialchars($this->castToString($this->value), ENT_QUOTES | ENT_HTML5) . '"';
	}

	/**
	 * Attempts to cast a value into a string without losing information.
	 *
	 * Throws an exception if the value cannot be cast to a string, without information loss.
	 */
	protected function castToString(mixed $value): string
	{
		if (is_string($value) || $value instanceof \Stringable) {
			return (string) $value;
		}

		// if casting to string the back to int is the same value, then no information lost
		if (is_int($value) && (int)(string) $value === $value) {
			return (string) $value;
		}

		// if casting to string the back to float is the same value, then no information lost
		if (is_float($value) && (float)(string) $value === $value) {
			return (string) $value;
		}

		if (is_object($value) && method_exists($value, '__toString')) {
			return (string) $value;
		}

		throw new \InvalidArgumentException('Value cannot be cast to a string without information loss.');
	}
}
