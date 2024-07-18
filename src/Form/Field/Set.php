<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;

final class Set implements \Countable, \IteratorAggregate
{
	private array $fields = [];

	public function __construct(Field ...$fields)
	{
		array_map([$this, 'add'], $fields);
	}

	public function map(callable $callback): self
	{
		return new self(...array_map($callback, $this->fields));
	}

	public function filter(callable $callback): self
	{
		return new self(...array_filter($this->fields, $callback));
	}

	public function __toArray(): array
	{
		return $this->fields;
	}

	/**
	 * Merge this set with another set.
	 *
	 * Only adds fields from the other set that do not already exist in this set.
	 */
	public function merge(self $other): self
	{
		$mergedFields = $this->fields;

		foreach ($other as $field) {
			if (!$this->exists($field)) {
				$mergedFields[] = $field;
			}
		}

		return new self(...$mergedFields);
	}

	public function apply(callable $callback): void
	{
		array_map($callback, $this->fields);
	}

	/**
	 * Find a field by its class name.
	 *
	 * @param class-string $fqcn
	 */
	public function find(string $fqcn): ?Field
	{
		foreach ($this->fields as $field) {
			if ($field instanceof $fqcn) {
				return $field;
			}
		}

		return null;
	}

	/**
	 * Find a field by its name.
	 */
	public function findByName(string $name): ?Field
	{
		foreach ($this->fields as $field) {
			if (strcasecmp($field->name, $name) === 0) {
				return $field;
			}
		}

		return null;
	}

	public function add(Field $field): void
	{
		$this->remove($field); // ensure no duplicates (attributes are unique by name

		$this->fields[] = $field;
	}

	public function exists(Field $field): bool
	{
		return $this->indexOf($field) !== null;
	}

	public function remove(Field $field): void
	{
		$index = $this->indexOf($field);

		if ($index !== null) {
			unset($this->field[$index]);
		}
	}

	public function indexOf(Field $field): ?int
	{
		foreach ($this->fields as $index => $existingField) {
			if (strcasecmp($existingField->name, $field->name) === 0) {
				return $index;
			}
		}

		return null;
	}

	public function count(): int
	{
		return count($this->fields);
	}

	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->fields);
	}
}
