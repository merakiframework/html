<?php
declare(strict_types=1);

namespace Meraki\Html\Form;

use Meraki\Html\Attribute;
use Meraki\Html\Exception\AttributesNotAllowed;
use Meraki\Html\Form\Field\Schema;
use Meraki\Html\Form\Field\Constraint;
use Meraki\Html\Element;
use Meraki\Html\Form\Field\ValidationResult;

abstract class Field extends Element
{
	public mixed $value = null;
	protected ?Attribute\Value $originalValue = null;
	public array $errors = [];
	public bool $valueHasChanged = false;
	public mixed $defaultValue = null;
	public bool $inputGiven = false;

	public static array $allowedAttributes = [
		Attribute\Id::class,
		Attribute\Hidden::class,	// global attribute...
		Attribute\Class_::class,
		Attribute\Style::class,
		Attribute\Data::class,
		Attribute\Label::class,
		Attribute\Type::class,
		Attribute\Name::class,
		Attribute\Required::class,
		Attribute\Disabled::class,
		Attribute\Readonly_::class,
		Attribute\Value::class,
		Attribute\Autocomplete::class,
		Attribute\Hint::class,
	];

	public function __construct(
		Attribute\Name $name,
		Attribute\Label $label,
		Attribute ...$otherAttributes
	) {
		$allAttributes = new Attribute\Set(
			...self::$allowedAttributes,
			...static::$allowedAttributes,
		);

		$allAttributes->set(
			$name,
			$label,
			$this->getType(),
			Attribute\Autocomplete::off(),		// fields should override this if they want to use it
			...$this->getDefaultAttributes(),
			...$otherAttributes
		);

		// @todo: implement required attributes

		$this->originalValue = $allAttributes->removeAndReturn(Attribute\Value::class);

		parent::__construct('div', $allAttributes);

		$this->reset();
	}

	abstract protected function getType(): Attribute\Type;
	abstract protected function getDefaultAttributes(): array;

	/** @todo: $value should be "Attribute\Value" */
	abstract public function validate(mixed $value): ValidationResult;

	public static function createFromSchema(array|object $schema): static
	{
		if (is_array($schema) && array_is_list($schema)) {
			throw new \InvalidArgumentException('Schema must be a key=>value array or an object.');
		}

		$schema = (object)$schema;

		if (!isset($schema->type)) {
			throw new \InvalidArgumentException('Field schema must have a "type".');
		}

		if (!isset($schema->name)) {
			throw new \InvalidArgumentException('Field schema must have a "name".');
		}

		if (!isset($schema->label)) {
			throw new \InvalidArgumentException('Field schema must have a "label".');
		}

		$type = $schema->type;
		$name = $schema->name;
		$label = $schema->label;

		unset($schema->type, $schema->name, $schema->label);

		$attributes = Attribute\Set::createFromSchema($schema);

		if (class_exists($type)) {
			return new $type(
				new Attribute\Name($name),
				new Attribute\Label($label),
				...$attributes->__toArray(),
			);
		}

		throw new \InvalidArgumentException('Field type "'.$type.'" does not exist.');
	}

	public function label(string $label): self
	{
		$this->attributes->set(new Attribute\Label($label));

		return $this;
	}

	public function hint(string $hint): self
	{
		$this->attributes->set(new Attribute\Hint($hint));

		return $this;
	}

	public function constrain(Constraint&Attribute $constraint): self
	{
		$this->attributes->set($constraint);

		return $this;
	}

	public function prefill(mixed $value): self
	{
		$value = new Attribute\Value($value);

		$this->originalValue = $value;
		$this->setValue($value);

		return $this;
	}

	/**
	 * Value to use if no value has been input.
	 */
	public function defaultTo(mixed $value): self
	{
		return $this->prefill($value);
	}

	public function enable(): self
	{
		$this->attributes->remove(new Attribute\Disabled());

		return $this;
	}

	public function editable(): self
	{
		$this->attributes->remove(new Attribute\Readonly_());

		return $this;
	}

	public function readonly(): self
	{
		$this->attributes->set(new Attribute\Readonly_());

		return $this;
	}

	public function optional(): self
	{
		$this->attributes->remove(new Attribute\Required());

		return $this;
	}

	public function inputRequired(): bool
	{
		$required =  $this->attributes->find(Attribute\Required::class);

		return $required !== null && $required->value === true;
	}

	protected function setValue(Attribute\Value $value): void
	{
		// "short circuit" the validation process
		// and set field errors to indicate field is required
		if ($this->inputRequired() && !$value->provided()) {
			$this->attributes->set($value);
			$this->errors = ['This field is required.'];
			$this->valueHasChanged = true;
			return;
		}

		$result = $this->validate($value->value);
		$value = new Attribute\Value($result->value);
		$this->errors = $result->errors;
		$this->valueHasChanged = !$this->originalValue->equals($value);

		$this->attributes->set($value);
	}

	public function isDisabled(): bool
	{
		$disabled = $this->attributes->find(Attribute\Disabled::class);

		return $disabled !== null && $disabled->value === true;
	}

	public function input(mixed $value): self
	{
		if ($this->isDisabled() || $this->isReadOnly()) {
			return $this;
		}

		$this->inputGiven = true;
		$this->setValue(new Attribute\Value($value));

		return $this;
	}

	public function isReadOnly(): bool
	{
		$readonly = $this->attributes->find(Attribute\Readonly_::class);

		return $readonly !== null && $readonly->value === true;
	}

	/**
	 * Clear the input value and reset the validation state.
	 */
	public function clear(): void
	{
		$this->attributes->remove(Attribute\Value::class);	// clear the "input" value
		$this->setValue(new Attribute\Value(null));			// update the validation state
	}

	/**
	 * Reset the field to its original state.
	 */
	public function reset(): self
	{
		$this->valueHasChanged = false;
		$this->errors = [];
		$this->inputGiven = false;

		if ($this->originalValue !== null) {
			$this->prefill($this->originalValue->value);
		} elseif ($this->isRequired()) {
			$this->prefill(null);
		}

		return $this;
	}

	public function makeLinkable(string $target, mixed $content = null): Field\Link
	{
		/** @var Attribute\Name $name */
		$name = $this->attributes->get(Attribute\Name::class);
		/** @var Attribute\Label $label */
		$label = $this->attributes->get(Attribute\Label::class);
		/** @var Attribute\Value $value */
		$value = $this->attributes->findOrCreate(Attribute\Value::class, fn() => new Attribute\Value($content));

		if (!$value->provided()) {
			throw new \RuntimeException('Field must have a value to be made linkable.');
		}

		if ($content === null) {
			$content = $value->value;
		}

		$link = new Field\Link($name, $label, $value);

		$link->appendContent($content);
		$link->targets($target);

		return $link;
	}

	public function addErrorMessage(string $message): self
	{
		$this->errors[] = $message;

		return $this;
	}

	public function addErrorMessages(string $message, string ...$messages): self
	{
		$this->errors = array_merge($this->errors, [$message], $messages);

		return $this;
	}

	public function valueIsDefault(): bool
	{
		return $this->valueHasChanged === false;
	}

	public function name(string $name): self
	{
		$this->attributes->add(new Attribute\Name($name));

		return $this;
	}

	public function disable(): self
	{
		$this->attributes->add(new Attribute\Disabled());

		return $this;
	}

	public function hasErrors(): bool
	{
		return count($this->errors) > 0;
	}

	public function isRequired(): bool
	{
		$required = $this->attributes->find(Attribute\Required::class);

		return $required !== null && $required->value === true;
	}

	public function isValid(): bool
	{
		return !$this->hasErrors();
	}
}
