<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;
use Meraki\Html\Form\Field\ValidationResult;

final class Link extends Field
{
	public static array $allowedAttributes = [
	];

	public string $target = '';

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('link');
	}

	public function getDefaultAttributes(): array
	{
		return [
			new Attribute\Readonly_()	// "link" fields are readonly by default
		];
	}

	public function targets(string $target): self
	{
		$this->target = $target;

		return $this;
	}

	public function validate(mixed $value): ValidationResult
	{
		if (!$this->canBeCastToString($value)) {
			return ValidationResult::failed($value, 'value must be a string');
		}

		return ValidationResult::passed((string)$value);
	}

	private function canBeCastToString(mixed $value): bool
	{
		return is_string($value)
			|| (is_object($value) && method_exists($value, '__toString'))
			|| $value instanceof \Stringable;
	}
}
