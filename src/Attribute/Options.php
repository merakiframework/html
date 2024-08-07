<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Options extends Attribute implements \IteratorAggregate, \Countable
{
	/** @var {string, string} $options */
	private array $options = [];

	/**
	 * @param array $allowedValues Is an array of allowed values as $name => $label.
	 */
	public function __construct(array $options)
	{
		parent::__construct('options', '');

		foreach ($options as $fieldName => $fieldLabel) {
			$this->add($fieldName, $fieldLabel);
		}
	}

	public function add(string $fieldName, string $fieldLabel): void
	{
		if (mb_strlen($fieldName) === 0) {
			throw new \InvalidArgumentException('Name for enum value must not be empty.');
		}

		if (mb_strlen($fieldLabel) === 0) {
			throw new \InvalidArgumentException('Label for enum value must not be empty.');
		}

		$this->options[$fieldName] = $fieldLabel;
		$this->updateValue();
	}

	public function remove(string $fieldName): void
	{
		if (isset($this->options[$fieldName])) {
			unset($this->options[$fieldName]);
			$this->updateValue();
		}
	}

	public function has(string $fieldName): bool
	{
		return isset($this->options[$fieldName]);
	}

	private function updateValue(): void
	{
		$value = '';

		// the allowed-values attribute has the same format as the style attribute
		// the part before the colon is the name of the enum value
		// the part after the colon is the label of the enum value
		foreach ($this->options as $fieldName => $fieldLabel) {
			$value .= $fieldName . ':' . $fieldLabel . ';';
		}

		$this->setValue($value);
	}

	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->options);
	}

	public function count(): int
	{
		return count($this->options);
	}
}
