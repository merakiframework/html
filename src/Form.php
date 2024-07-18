<?php
declare(strict_types=1);

namespace Meraki\Html;

use Meraki\Html\Form\Schema;
use Meraki\Html\Form\Field;

/**
 * Represents an HTML form element.
 *
 * @property-read string $name
 * @property-read string $action
 * @property-read string $method
 */
final class Form extends Element
{
	public Field\Set $fields;

	public bool $submitted = false;
	public bool $changesMade = false;
	public bool $canBeReset = false;
	public bool $canBeCancelled = false;
	public string $cancelAction = '';

	public function __construct(string $name, string $action, string $method = 'POST')
	{
		$attributes = Attribute\Set::useGlobal(
			Attribute\Name::class,
			Attribute\Action::class,
			Attribute\Method::class,
		);
		$attributes->add(
			new Attribute\Name($name),
			new Attribute\Action($action),
			new Attribute\Method($method),
		);
		parent::__construct('form', $attributes);

		$this->fields = new Field\Set();
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
		$this->attributes['method'] = strtoupper($method);

		return $this;
	}

	public function withAction(string $action): self
	{
		$this->attributes['action'] = $action;

		return $this;
	}

	/**
	 * Replace placeholders in the action attribute with the given data
	 */
	public function expandAction(array $data): self
	{
		$action = $this->attributes['action'];

		foreach ($data as $key => $value) {
			$action = str_replace('{' . $key . '}', $value, $action);
		}

		$this->attributes['action'] = $action;

		return $this;
	}

	public function prefill(array|object $data): self
	{
		$data = (array)$data;

		/** @var Field $field */
		foreach ($this->fields as $field) {
			if (isset($data[$field->name->name])) {
				$field->prefill($data[$field->name->name]);
			}
		}

		return $this;
	}

	public function allowCancelling(string $cancelAction = ''): self
	{
		$this->canBeCancelled = true;
		$this->cancelAction = $cancelAction;
		return $this;
	}

	public function allowResetting(): self
	{
		$this->canBeReset = true;

		return $this;
	}

	public static function createFromSchema(array|\stdClass|Schema $schema): self
	{
		if (is_array($schema) && array_is_list($schema)) {
			throw new \InvalidArgumentException('Schema must be an array of key:value pairs or an object.');
		}

		if (is_array($schema)) {
			$schema = (object) $schema;
		}

		if (!isset($schema->fields) && !is_array($schema->fields)) {
			throw new \InvalidArgumentException('Schema must have at least one field defined.');
		}

		$fields = $schema->fields;
		unset($schema->fields);
		$attributes = (array) $schema;

		return new self($attributes, $fields);
	}

	public function skipValidation(): self
	{
		$this->attributes->add(new Attribute\Novalidate());

		return $this;
	}

	public function requireValidation(): self
	{
		$this->attributes->remove(new Attribute\Novalidate());

		return $this;
	}

	public function addErrors(array $errors): self
	{
		foreach ($errors as $name => $message) {
			if ($field = $this->fields->findByName($name)) {
				$field->addError($message);
			}
		}

		return $this;
	}

	public function process(array $data): bool
	{
		// do not submit if already submitted
		if ($this->submitted) {
			return true;
		}

		foreach ($this->fields as $field) {
			// "enter" the data into the form field
			if (isset($data[$field->name->name])) {
				$field->input($data[$field->name->name]);
			}

			// check if the field has changed
			if ($field->valueHasChanged) {
				$this->changesMade = true;
			}
		}

		// form is submitted if it is valid or no changes were made
		$this->submitted = $this->isValid() || ($this->changesMade === false);

		return $this->submitted;
	}

	public function isValid(): bool
	{
		foreach ($this->fields as $field) {
			if (!$field->isValid()) {
				return false;
			}
		}

		return true;
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

	public function setFields(array $fields): void
	{
		$this->fields = new Field\Set(...$fields);
	}

	public function addFields(array $fields): void
	{
		array_map([$this, 'addField'], $fields);
	}

	public function addField(Field $field, Field ...$fields): void
	{
		$this->fields = $this->fields->merge(new Field\Set($field, ...$fields));
	}

	public function fieldExists(Field $field): bool
	{
		foreach ($this->fields as $existingField) {
			if ($existingField->name === $field->name) {
				return true;
			}
		}

		return false;
	}

	public function setField(Field $field): void
	{
		$index = $this->indexOfField($field);

		if ($index !== null) {
			$this->fields[$index] = $field;
		}

		$this->addField($field);
	}

	public function indexOfField(Field $field): ?int
	{
		foreach ($this->fields as $index => $existingField) {
			if ($existingField->name->equals($field->name)) {
				return $index;
			}
		}

		return null;
	}
}
