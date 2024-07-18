<?php
declare(strict_types=1);

namespace Meraki\Html\Form;

use Ramsey\Uuid\Uuid;

class Schema implements \ArrayAccess
{
	// public \stdClass $data;
	// private \stdClass $originalSchema;
	public array $attributes = [];
	public array $fields = [];

	private function setDefaults(object $data): object
	{
		foreach ($data->fields as &$field) {
			if (!isset($field->name)) {
				throw new \InvalidArgumentException('Each field must have a name.');
			}

			if (!isset($field->type)) {
				throw new \InvalidArgumentException('Each field must have a type.');
			}

			// convert type to object if it's a string
			// @todo Remove as this is for backwards compatibility
			if (!($field->type instanceof FormSchemaFieldType)) {
				$field->type = FormSchemaFieldType::from($field->type);
			}

			if (!isset($field->label)) {
				throw new \InvalidArgumentException('Each field must have a label.');
			}

			$field->constraints = $field->constraints ?? new \stdClass();

			// add default constraints if they are not already set
			$defaultConstraints = self::DEFAULT_CONSTRAINTS_FOR_TYPE[$field->type->value] ?? [];


			foreach ($defaultConstraints as $name => $value) {
				if (!isset($field->constraints->$name)) {
					$field->constraints->$name = $value;
				}
			}
		}

		return $data;
	}

	private function convertToObject(array|object $data): mixed
	{
		// convert object to array for iteration
		if (is_object($data)) {
			$data = (array) $data;
		}

		// recursively traverse structure converting any assoc arrays to objects
		foreach ($data as &$value) {
			if (is_array($value)) {
				$value = $this->convertToObject($value);
			}
		}

		return !array_is_list($data) ? (object) $data : $data;
	}

	public function get(): \stdClass
	{
		return $this->originalSchema;
	}

	public function __isset($name)
	{
		foreach ($this->data->fields as $field) {
			if ($field->name === $name) {
				return true;
			}
		}

		return false;
	}

	public function offsetExists($name): bool
	{
		return $this->__isset($name);
	}

	public function offsetGet($name): mixed
	{
		return $this->__get($name);
	}

	public function offsetSet(mixed $name, mixed $value): void
	{
		throw new \RuntimeException('Cannot dynamically add to schema');
	}

	public function offsetUnset($name): void
	{
		throw new \RuntimeException('Cannot dynamically remove from schema');
	}

	public function __get($name)
	{
		foreach ($this->data->fields as $field) {
			if ($field->name === $name) {
				return $field;
			}
		}

		throw new \RuntimeException("Field with name of '$name' does not exist.");
	}

	public function __clone()
	{
		$this->data = clone $this->data;
	}

	public function validateNewWay(array $data): ValidatedFormSchema
	{
		return new ValidatedFormSchema($this->originalSchema, $data);
	}

	public function validate(array $data): \stdClass
	{
		$schema = clone $this->data;

		foreach ($schema->fields as $property) {
			// disabled fields do not need validation
			if (isset($property->disabled) && $property->disabled) {
				$property->valueHasChanged = false;
				$property->errors = [];
				continue;
			}

			$value = $data[$property->name] ?? null;
			$property->valueHasChanged = isset($property->value) && $property->value !== $value;
			$property->value = $value;

			// Check if the property is required
			if ($this->hasConstraint('required', $property) && $property->constraints->required) {
				if ($value === null || $value === '') {
					$property->errors = ['This field is required.'];
					continue;
				}
			}

			$property->errors = match ($property->type) {
				FormSchemaFieldType::uuid => $this->validateUuid($property),
				FormSchemaFieldType::string => $this->validateAsString($property),
				FormSchemaFieldType::datetime => $this->validateAsDateTime($property),
				FormSchemaFieldType::phone => $this->validateAsPhone($property),
				FormSchemaFieldType::email => $this->validateAsEmail($property),
				FormSchemaFieldType::integer => $this->validateAsInteger($property),
				FormSchemaFieldType::number => $this->validateAsNumber($property),
				FormSchemaFieldType::boolean => $this->validateAsBoolean($property),
				FormSchemaFieldType::enum => $this->validateAsEnum($property),
				FormSchemaFieldType::date => $this->validateAsDate($property),
				FormSchemaFieldType::url => $this->validateAsURL($property),
				FormSchemaFieldType::name => $this->validateAsName($property),
				default => throw new \RuntimeException("Unknown type: {$property->type->value}"),
			};

			// check if a unique constraint is set
			if ($this->hasConstraint('unique', $property)) {
				$unique = $property->constraints->unique;

				if (is_callable($unique)) {
					try {
						$unique = $unique($value);
					} catch (\DomainException $e) {
						$property->errors[] = $e->getMessage();
						continue;
					}
				}
			}
		}

		return $schema;
	}

	private function validateAsText($property): array
	{
		return [...$this->checkLength($property), ...$this->checkPattern($property)];
	}

	private function validateAsName($property): array
	{
		// $fullNameRegex = "^[\p{L}\.'\-]+(?: [\p{L}\.'\-]+)*$";

		// if (preg_match($fullNameRegex, $property->value) !== 1) {
		// 	return ['A name can only contain letters, hyphens, and apostrophes'];
		// }

		return [...$this->checkLength($property), ...$this->checkPattern($property)];
	}

	private function validateAsURL($property): array
	{
		if (filter_var($property->value, FILTER_VALIDATE_URL) === false) {
			return ['Invalid URL.'];
		}

		return [];
	}

	private function validateAsDate($property): array
	{
		try {
			$value = LocalDateTime::createFromFormat('Y-m-d', $property->value);
		} catch (\ValueError $e) {
			return ['Invalid date format.'];
		}

		if ($value === false) {
			return ['Invalid date format.'];
		}

		$errors = [];

		if (isset($property->constraints->min)) {
			$min = LocalDateTime::createFromFormat('Y-m-d', $property->constraints->min);

			if ($value < $min) {
				$errors[] = 'Date must be greater than or equal to ' . $min->format('d-m-Y') . '.';
			}
		}

		if (isset($property->constraints->max)) {
			$max = LocalDateTime::createFromFormat('Y-m-d', $property->constraints->max);

			if ($value > $max) {
				$errors[] = 'Date must be less than or equal to ' . $max->format('d-m-Y') . '.';
			}
		}

		// validate step (which is in seconds)
		if (isset($property->constraints->step)) {
			$step = DateTimeInterval::fromSeconds($property->constraints->step);

			if (!$value->intervalOf($step)) {
				$errors[] = 'Date must be in increments of ' . $step->formatAsHumanReadable() . '.';
			}
		}

		return $errors;
	}

	private function hasConstraint($name, $property): bool
	{
		return isset($property->constraints) && isset($property->constraints->$name);
	}

	private function validateUuid($property): array
	{
		if (!Uuid::isValid($property->value)) {
			$errors[] = 'Invalid UUID.';
		}

		if (isset($property->constraints->version) && $property->constraints->version !== Uuid::fromString($property->value)->getVersion()) {
			$errors[] = 'Invalid UUID version.';
		}

		return $errors;
	}

	private function validateAsString($property): array
	{
		$property->multiline = $property->multiline ?? false;

		if (!$property->multiline && preg_match('/\R/u', $property->value) === 1) {
			return ['Value must not contain newlines.'];
		}

		return [...$this->checkLength($property), ...$this->checkPattern($property)];
	}

	private function validateAsDateTime($property): array
	{
		try {
			$value = LocalDateTime::createFromFormat('Y-m-d\TH:i', $property->value);
		} catch (\ValueError $e) {
			return ['Invalid date and time format.'];
		}

		if ($value === false) {
			return ['Invalid date and time format.'];
		}

		$errors = [];

		if (isset($property->constraints->min)) {
			$min = LocalDateTime::createFromFormat('Y-m-d\TH:i', $property->constraints->min);
			if ($value < $min) {
				$errors[] = 'Date and time must be greater than or equal to ' . $min->format('d-m-Y h:i A') . '.';
			}
		}

		if (isset($property->constraints->max)) {
			$max = LocalDateTime::createFromFormat('Y-m-d\TH:i', $property->constraints->max);
			if ($value > $max) {
				$errors[] = 'Date and time must be less than or equal to ' . $max->format('d-m-Y h:i A') . '.';
			}
		}

		// validate step (which is in seconds)
		if (isset($property->constraints->step)) {
			$step = DateTimeInterval::fromSeconds($property->constraints->step);

			if (!$value->intervalOf($step)) {
				$errors[] = 'Date and time must be in increments of ' . $step->formatAsHumanReadable() . '.';
			}
		}

		return $errors;
	}

	private function checkPattern($property): array
	{
		if (isset($property->constraints->pattern) && !preg_match('/' . $property->constraints->pattern . '/u', $property->value)) {
			return ['Value does not match the required pattern.'];
		}

		return [];
	}

	private function validateAsPhone($property): array
	{
		// "+" followed by one or more digits
		if (!preg_match('/^\+?\d+$/', $property->value)) {
			return ['Must be a valid phone number.'];
		}

		return array_merge(
			[],
			$this->checkLength($property),
			$this->checkPattern($property),
		);
	}

	private function checkLength($property): array
	{
		$errors = [];

		if (isset($property->constraints->min) && mb_strlen($property->value, 'utf-16') < $property->constraints->min) {
			$errors[] = 'Must be at least ' . $property->constraints->min . ' characters long.';
		}

		if (isset($property->constraints->max) && mb_strlen($property->value, 'utf-16') > $property->constraints->max) {
			$errors[] = 'Must be no more than ' . $property->constraints->max . ' characters long.';
		}

		return $errors;
	}

	private function validateAsEmail($property): array
	{
		if (
			!str_contains($property->value, '@')
			|| str_starts_with($property->value, '@')
			|| str_ends_with($property->value, '@')
			|| str_contains($property->value, '..')
			|| str_contains($property->value, '.@')
			|| str_contains($property->value, '@.')
		) {
			return ['Email address is not in a valid format.'];
		}

		return array_merge(
			[],
			$this->checkLength($property),
			$this->checkPattern($property),
		);
	}

	private function validateAsInteger($property): array
	{
		if (!ctype_digit($property->value)) {
			return ['Please provide digits only.'];
		}

		return array_merge(
			[],
			$this->checkLength($property),
			$this->checkPattern($property),
			$this->checkMinMax($property, (int) $property->value),
		);
	}

	private function checkMinMax($property, int|float $castedValue): array
	{
		$errors = [];

		if (isset($property->constraints->min) && $castedValue < $property->constraints->min) {
			$errors[] = 'Value must be greater than or equal to' . $property->constraints->min . '.';
		}

		if (isset($property->constraints->max) && $castedValue > $property->constraints->max) {
			$errors[] = 'Value must be less than or equal to' . $property->constraints->max . '.';
		}

		return $errors;
	}

	private function validateAsNumber($property): array
	{
		if (!is_numeric($property->value)) {
			return ['Please provide a number.'];
		}

		return array_merge(
			[],
			$this->checkLength($property),
			$this->checkPattern($property),
			$this->checkMinMax($property, (float) $property->value),
		);
	}

	private function validateAsBoolean($property): array
	{
		if (!is_null($property->value)) {
			if (strcasecmp($property->value, 'on') !== 0 && strcasecmp($property->value, 'off') !== 0) {
				return ['Please provide a yes or no answer.'];
			}
		}

		return [];
	}

	private function validateAsEnum($property): array
	{
		if (isset($property->oneOf)) {
			$containsAtLeastOneOf = false;

			foreach ($property->oneOf as $option) {
				if ($option->name === $property->value) {
					$containsAtLeastOneOf = true;
					break;
				}
			}

			if (!$containsAtLeastOneOf) {
				return ['Invalid value. Choose one of the provided options.'];
			}
		}

		return [];
	}
}
