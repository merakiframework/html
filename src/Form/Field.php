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
	private array $cache = [];
	public mixed $value = null;
	public mixed $originalValue = null;
	public array $errors = [];
	public string $hint = '';
	public string $placeholder = '';
	// public bool $disabled = false;
	// public bool $readonly = false;
	public bool $valueHasChanged = false;
	public mixed $defaultValue = null;
	public string $mask = '';

	public mixed $providedValue = null;

	public bool $inputGiven = false;
	private bool $isPrefilled = false;
	protected Element $label;
	protected Element $input;
	private Element $errorContainer;
	public static array $allowedAttributes = [
		Attribute\Id::class,
		// Attribute\Placeholder::class,
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

		parent::__construct('div', $allAttributes);
	}

	abstract protected function getType(): Attribute\Type;
	abstract protected function getDefaultAttributes(): array;

	public function label(string $label): self
	{
		$this->label->setContent($label);

		return $this;
	}

	public function constrain(Constraint&Attribute $constraint): self
	{
		$this->attributes->add($constraint);

		return $this;
	}

	public function reset(): self
	{
		$value = $this->originalValue;
		$this->originalValue = null;
		$this->value = null;
		$this->valueHasChanged = false;
		$this->errors = [];
		$this->inputGiven = false;
		$this->isPrefilled = false;

		if ($value !== null) {
			$this->prefill($value);
		}

		return $this;
	}

	public function prefill(mixed $value): self
	{
		// if value is null, then the field is no longer prefilled
		if ($value === null) {
			$this->isPrefilled = false;
			return $this;
		}

		if ($this->isPrefilled) {
			return $this;
		}

		// var_dump($value);
		$required = $this->attributes->required;
		$this->attributes->required = false;

		$this->setValue($value);

		$this->isPrefilled = true;
		$this->attributes->required = $required;

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

	public function readOnly(): self
	{
		$this->attributes->add(new Attribute\Readonly_());

		return $this;
	}

	// public function readonly(): self
	// {
	// 	$this->readonly = true;

	// 	return $this;
	// }

	public function optional(): self
	{
		$this->attributes->required = false;
		//$this->attributes->remove(new Attribute\Required());

		return $this;
	}

	private function inputRequired(): bool
	{
		return $this->attributes->contains(Attribute\Required::class);
	}

	protected function setValue(mixed $value): void
	{
		// "short circuit" the validation process
		// and set field errors to indicate field is required
		if ($this->inputRequired() && ($value === null || $value === '')) {
			$this->value = null;
			$this->errors = ['This field is required.'];
			$this->valueHasChanged = true;
			return;
		}

		$originalValue = $this->value;
		$result = $this->validate($value);
		$this->value = $result->value;
		$this->errors = $result->errors;

		if ($this->value !== $originalValue) {
			$this->valueHasChanged = true;
		}
	}

	public function input(mixed $value): self
	{
		// disabled fields cannot be modified
		if ($this->disabled) {
			return $this;
		}

		$this->inputGiven = true;
		$this->setValue($value);

		return $this;


		// // var_dump($this->value, $value);

		// $this->inputGiven = true;
		// // $this->providedValue = $value;
		// $originalValue = $this->value;

		// $this->setValue($value);


		// // check if value has changed from what was "prefilled" originally
		// if ($this->value instanceof \DateTimeInterface) {
		// 	// $this->valueHasChanged = $this->value->format('Y-m-d H:i:s') !== $originalValue->format('Y-m-d H:i:s');
		// } elseif ($this->value instanceof \Stringable) {
		// 	// $this->valueHasChanged = (string)$this->value === (string)$originalValue;
		// } elseif (is_object($this->value)) {
		// 	// $this->valueHasChanged = $this->value == $originalValue;
		// } elseif ($value !== $originalValue || $this->value !== $originalValue) {
		// 	$this->valueHasChanged = true;
		// }

		// return $this;
	}

	public function clear(): void
	{
		$this->value = $this->originalValue;
		$this->originalValue = null;
		$this->valueHasChanged = false;
		$this->errors = [];
		$this->inputGiven = false;
		$this->isPrefilled = false;
	}

	abstract public function validate(mixed $value): ValidationResult;

	/**
	 * Converts a field into a LinkableField.
	 *
	 * A linkable field allows the user to click on the text
	 * inside the field to perform an action.
	 */
	public function linkTo(string $href, string $text = ''): self
	{
		if (strlen($text) === 0) {
			$text = $this->value;
		}

		return $this;
	}

	public function addError(string $message): self
	{
		$this->errors[] = $message;

		return $this;
	}

	public function valueIsDefault(): bool
	{
		return $this->valueHasChanged === false;
	}

	public static function createFromSchema(Schema $schema): static
	{
		return $schema->createField();
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

	public function isValid(): bool
	{
		if ($this->disabled) {
			return true;
		}

		if ($this->inputGiven) {
			// Input given and has no errors
			return !$this->hasErrors()
				// or input given and is the same as prefill/original value
				|| ($this->isPrefilled && !$this->valueHasChanged)
				// or not required and value is null
				|| (!$this->attributes->required && $this->value === null);
		}

		// No input given and field not required
		return !$this->attributes->required;
		// field is valid if...
		// no input given and field not required
		// if (!$this->inputGiven && !$this->attributes->required) {
		// 	return true;
		// }

		// // or input given and no errors
		// return $this->inputGiven && !$this->hasErrors();
	}
}
