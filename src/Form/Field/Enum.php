<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Attribute;
use Meraki\Html\Form\Field;
use Meraki\Html\Form\Field\ValidationResult;

final class Enum extends Field
{
	public static array $allowedAttributes = [
		Attribute\Options::class,
	];

	public static array $requiredOptions = [
		Attribute\Options::class,
	];

	public function __construct(
		Attribute\Name $name,
		Attribute\Label $label,
		Attribute ...$otherAttributes
	) {
		$options = array_filter($otherAttributes, fn($attribute) => $attribute instanceof Attribute\Options);

		if (count($options) === 0) {
			$otherAttributes[] = new Attribute\Options([]);
		}

		parent::__construct($name, $label, ...$otherAttributes);
	}

	public static function create(string $name, string $label, Attribute ...$attributes): self
	{
		return new self(new Attribute\Name($name), new Attribute\Label($label), ...$attributes);
	}

	public function addOption(string $name, string $label): void
	{
		$this->attributes->get(Attribute\Options::class)->add($name, $label);
	}

	public function getOption(string $name): ?string
	{
		return $this->attributes->get(Attribute\Options::class)->find($name);
	}

	public function hasOption(string $name): bool
	{
		return $this->attributes->get(Attribute\Options::class)->has($name);
	}

	public function removeOption(string $name): void
	{
		$this->attributes->get(Attribute\Options::class)->remove($name);
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

		if ($this->hasOption($value)) {
			return ValidationResult::success($value);
		}

		return ValidationResult::failed($value, "Value '$value' is not a valid option.");
	}
}
