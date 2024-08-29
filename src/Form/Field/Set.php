<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;

final class Set implements \Countable, \IteratorAggregate
{
	private array $fields = [];

	public function __construct(Field ...$fields)
	{
		array_map([$this, 'add'], $fields);
	}

	public static function createFromArrayList(array $fields): self
	{
		return new self(...$fields);
	}

	public static function tryCreateFromArrayList(array $fields): ?self
	{
		if (self::isArrayListInstancesOfField($fields)) {
			return new self(...$fields);
		}

		return null;
	}

	private static function isArrayListInstancesOfField(array $fields): bool
	{
		if (empty($fields) || !array_is_list($fields)) {
			return false;
		}

		foreach ($fields as $field) {
			if (!$field instanceof Field) {
				return false;
			}
		}

		return true;
	}

	public static function createFromSchema(array $schema, ?Factory $factory = null): self
	{
		if (is_array($schema) && !array_is_list($schema)) {
			throw new \InvalidArgumentException('Schema must be a list of field schemas.');
		}

		$self = new self();

		foreach ($schema as $fieldSchema) {
			if (is_object($fieldSchema) || (is_array($fieldSchema) && !array_is_list($fieldSchema))) {
				$fieldSchema = (object) $fieldSchema;

				// look for field type
				if (!isset($fieldSchema->type)) {
					throw new \InvalidArgumentException('Field schema must have a "type".');
				}

				$type = $fieldSchema->type;

				if (class_exists($type)) {
					$self->add($type::createFromSchema($fieldSchema));
					continue;
				}

				throw new \InvalidArgumentException('Field type "' . $type . '" does not exist.');
			}

			throw new \InvalidArgumentException('Field schema must be an key=>value array or an object.');
		}

		return $self;
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
			if ($field->name->equals(new Attribute\Name($name))) {
				return $field;
			}
		}

		return null;
	}

	public function add(Field $field): void
	{
		$this->remove($field); // ensure no duplicates (attributes are unique by name)

		$this->fields[] = $field;
	}

	/**
	 * Check if all fields pass a given test.
	 */
	public function every(callable $callback): bool
	{
		foreach ($this->fields as $field) {
			if (!$callback($field)) {
				return false;
			}
		}

		return true;
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
			if ($field->name->equals($existingField->name)) {
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
