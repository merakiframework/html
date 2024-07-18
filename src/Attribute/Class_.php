<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

/**
 * Attribute for specifying one or more class names for an element.
 *
 * A class name is case-sensitive, therefore "myClass" and
 * "myclass" are considered different classes.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/class
 */
final class Class_ extends Attribute
{
	public array $names = [];

	public function __construct(string ...$names)
	{
		$this->setName('class');

		if (count($names) > 0) {
			$this->add(...$names);
		}
	}

	/**
	 * Add one or more class names to the class attribute.
	 */
	public function add(string $name, string ...$names): void
	{
		$this->names = array_merge($this->names, [$name], $names);
		$this->setValue($this->names);
	}

	/**
	 * Add one or more class names to the class attribute if the condition is true.
	 */
	public function conditionalAdd(bool $condition, string $name, string ...$names): void
	{
		if ($condition) {
			$this->add($name, ...$names);
		}
	}

	/**
	 * Add one or more class names to the class attribute if the condition is true, otherwise remove them.
	 */
	public function conditionalToggle(bool $condition, string $name, string ...$names): void
	{
		if ($condition) {
			$this->add($name, ...$names);
		} else {
			$this->remove($name, ...$names);
		}
	}

	/**
	 * Remove one or more class names from the class attribute.
	 */
	public function remove(string $name, string ...$names): void
	{
		$this->names = array_diff($this->names, [$name], $names);
		$this->setValue($this->names);
	}

	/**
	 * Toggle one or more class names in the class attribute.
	 */
	public function toggle(string $name, string ...$names): void
	{
		foreach ([$name, ...$names] as $name) {
			if ($this->contains($name)) {
				$this->remove($name);
			} else {
				$this->add($name);
			}
		}
	}

	/**
	 * Check if one or more class names exist in the class attribute.
	 */
	public function exists(string $name, string ...$names): bool
	{
		$names = [$name, ...$names];

		foreach ($names as $name) {
			if (!in_array($name, $this->names, true)) {
				return false;
			}
		}

		return true;
	}

	public function clear(): void
	{
		$this->names = [];
		$this->setValue($this->names);
	}

	/**
	 * Check if one or more class names exist in the class attribute.
	 */
	public function contains(string $name, string ...$names): bool
	{
		$names = [$name, ...$names];

		foreach ($names as $name) {
			if (!in_array($name, $this->names, true)) {
				return false;
			}
		}

		return true;
	}

	protected function setValue(mixed $value): void
	{
		if (is_array($value)) {
			$value = implode(' ', $value);
		}

		$value = trim($value);

		if (empty($value)) {
			throw new \InvalidArgumentException('The "class" attribute can not be empty.');
		}

		parent::setValue($value);
	}
}
