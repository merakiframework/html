<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Attribute;
use Meraki\Html\Form\Field;
use Meraki\Html\Form\Field\ValidationResult;
use InvalidArgumentException;

final class Enum extends Field
{
	public static array $allowedAttributes = [
		//Attribute\Multiple::class,
	];

	public array $options = [];

	public function __construct(
		Attribute\Name $name,
		Attribute\Label $label,
		array $options,
		Attribute ...$otherAttributes
	) {
		parent::__construct($name, $label, ...$otherAttributes);

		foreach ($options as $name => $label) {
			$this->addOption($name, $label);
		}
	}

	public static function create(string $name, string $label, array $options, Attribute ...$attributes): self
	{
		return new self(new Attribute\Name($name), new Attribute\Label($label), $options, ...$attributes);
	}

	public function addOption(string $name, string $label): void
	{
		$this->options[$name] = $label;
	}

	public function getOption(string $name): ?string
	{
		return $this->options[$name] ?? null;
	}

	public function hasOption(string $name): bool
	{
		return isset($this->options[$name]);
	}

	public function removeOption(string $name): void
	{
		unset($this->options[$name]);
	}

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('enum');
	}

	public function getDefaultAttributes(): array
	{
		return [];
	}

	public function validate(mixed $value): ValidationResult
	{
		if (!is_string($value)) {
			return ValidationResult::failed($value, 'Value is not a string.');
		}

		if (count($this->options) === 0) {
			throw new InvalidArgumentException('No options have been set for this enum field.');
		}

		if ($this->hasOption($value)) {
			return ValidationResult::success($value);
		}

		return ValidationResult::failed($value, "Value '$value' is not a valid option.");
	}
}
