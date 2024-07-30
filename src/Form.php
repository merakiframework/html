<?php
declare(strict_types=1);

namespace Meraki\Html;

use Meraki\Html\Form\Field;
use Meraki\Html\Form\Field\ValidationResult;

/**
 * Represents an HTML form element.
 */
final class Form extends Element
{
	/**
	 * @todo This should be a nodelist of fields and probably renamed to content/children.
	 */
	public Field\Set $fields;
	public bool $submitted = false;
	public bool $changesMade = false;

	public function __construct(?Attribute\Set $attrs = null, ?Field\Set $fields = null)
	{
		$attrs = ($attrs ?? new Attribute\Set())
			->allowGlobal()
			->allow(
				Attribute\Name::class,
				Attribute\Action::class,
				Attribute\Method::class,
				Attribute\Novalidate::class,
			);

		parent::__construct('form', $attrs);

		$this->fields = $fields ?? new Field\Set();
	}

	public static function putTo(string $path, array $placeholders = []): self
	{
		return (new self())->put($path, $placeholders);
	}

	public static function postTo(string $path, array $placeholders = []): self
	{
		return (new self())->post($path, $placeholders);
	}

	public function put(string $path, array $placeholders = [])
	{
		return $this->withMethod('put')->withAction($path)->expandAction($placeholders);
	}

	public function withMethod(string $method): self
	{
		$this->attributes->set(new Attribute\Method($method));

		return $this;
	}

	public function withAction(string $action): self
	{
		$this->attributes->set(new Attribute\Action($action));

		return $this;
	}

	public function prefill(array|object $data): self
	{
		$data = (array)$data;

		/** @var Field $field */
		foreach ($this->fields as $field) {
			$fieldName = $field->attributes->get(Attribute\Name::class);

			if (isset($data[$fieldName->value])) {
				$field->prefill($data[$fieldName->value]);
			}
		}

		return $this;
	}

	public static function createFromSchema(array|\stdClass $schema): self
	{
		if (is_array($schema) && array_is_list($schema)) {
			throw new \InvalidArgumentException('Schema must be an array of key=>value pairs or an object.');
		}

		$attributes = (object)$schema;
		$fields = $attributes->fields ?? [];

		unset($attributes->fields);

		return new self(
			Attribute\Set::createFromSchema($attributes),
			Field\Set::tryCreateFromArrayList($fields) ?? Field\Set::createFromSchema($fields),
		);
	}

	public function skipValidation(): self
	{
		$this->attributes->add(new Attribute\Novalidate());

		return $this;
	}

	public function requireValidation(): self
	{
		$this->attributes->remove(Attribute\Novalidate::class);

		return $this;
	}

	public function addErrorMessagesToFields(array $errors): self
	{
		foreach ($errors as $fieldName => $messages) {
			$messages = (array)$messages;

			if ($field = $this->fields->findByName($fieldName)) {
				$field->addErrorMessages(...$messages);
			}
		}

		return $this;
	}

	public function validate(array $data): ValidationResult
	{

	}

	public function process(array $data): bool
	{
		if ($this->submitted) {
			return true;
		}

		foreach ($this->fields as $field) {
			$fieldName = $field->attributes->get(Attribute\Name::class);

			// "enter" the data into the form field
			if (isset($data[$fieldName->value])) {
				$field->input($data[$fieldName->value]);
			}

			// check if the field has changed
			if ($field->valueHasChanged) {
				$this->changesMade = true;
			}
		}

		// a form is considered submitted if it is valid or no changes were made
		$this->submitted = $this->isValid() || ($this->changesMade === false);

		return $this->submitted;
	}

	public function isValid(): bool
	{
		return $this->fields->every(fn(Field $field) => $field->isValid());
	}

	public function reset(): self
	{
		$this->fields->apply(fn(Field $field) => $field->reset());

		$this->submitted = false;
		$this->changesMade = false;

		return $this;
	}

	public function getChangedFields(): array
	{
		return $this->fields->filter(fn(Field $field) => $field->valueHasChanged)->__toArray();
	}
}
