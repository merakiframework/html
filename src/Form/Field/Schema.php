<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Attribute;
use Meraki\Html\Attribute\Type;
use Meraki\Html\Form\Field;

final class Schema
{
	private Attribute\Type $type;
	private Attribute\Name $name;
	private Attribute\Label $label;
	private array $attributes;
	public function __construct(object $schema)
	{
		if (!isset($schema->type)) {
			throw new \InvalidArgumentException('Field schema must have a type.');
		}

		if (!isset($schema->name)) {
			throw new \InvalidArgumentException('Field schema must have a name.');
		}

		if (!isset($schema->label)) {
			throw new \InvalidArgumentException('Field schema must have a label.');
		}

		// all properties are considered attributes
		$attributes = get_object_vars($schema);
		unset($attributes['name'], $attributes['label'], $attributes['type']);

		$this->type = new Attribute\Type($schema->type);
		$this->name = new Attribute\Name($schema->name);
		$this->label = new Attribute\Label($schema->label);
		$this->attributes = $attributes;
	}

	public static function fromArray(array $schema): self
	{
		return new self((object) $schema);
	}

	public static function fromObject(object $schema): self
	{
		return new self($schema);
	}

	public function createField(): Field
	{
		$fieldClass = $this->getFieldClass();

		$field = new $fieldClass($this->name, $this->label);

		if ($this->value !== null) {
			$field->prefill($this->value);
		}

		if ($this->hint !== null) {
			$field->hint($this->hint);
		}

		if (isset($this->attributes->disabled) && $this->attributes->disabled) {
			$field->disable();
		}

		if (isset($this->attributes->readonly) && $this->attributes->readonly) {
			$field->readOnly();
		}

		// if (isset($this->attributes->placeholder)) {
		// }

		// if (isset($this->attributes->mask)) {
		// 	// $field->mask($this->attributes->mask);
		// }

		return $field;
	}

	private function getFieldClass(): string
	{
		$className = explode('-', $this->type->value);
		$className = array_map('ucfirst', $className);
		$className = implode('', $className);

		return __NAMESPACE__ . '\\' . $className;
	}
}
