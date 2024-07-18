<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;
use Meraki\Html\Form\Field\ValidationResult;

final class Boolean extends Field
{
	public static array $allowedAttributes = [
		Attribute\Checked::class
	];

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('boolean');
	}

	public function getDefaultAttributes(): array
	{
		return [];
	}

	public static function checked(): self
	{

	}

	public static function unchecked(): self
	{

	}

	public function check(): self
	{
		$this->attributes->add(new Attribute\Checked());

		return $this;
	}

	public function isChecked(): bool
	{
		return $this->attributes->contains(Attribute\Checked::class);
	}

	public function uncheck(): self
	{
		$this->attributes->remove(Attribute\Checked::class);

		return $this;
	}

	public function validate(mixed $value): ValidationResult
	{
		if (is_string($value)) {
			$value = mb_strtolower($value);
		}

		if (in_array($value, ['0', 0, 'off', false], true)) {
			return ValidationResult::success(false);
		}

		// should this support other values, like HTML standard?
		if (in_array($value, ['1', 1, 'on', true], true)) {
			return ValidationResult::success(true);
		}

		return ValidationResult::failed($value, 'Value must be a boolean.');
	}
}
