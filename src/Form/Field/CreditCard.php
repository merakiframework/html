<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;
use Meraki\Html\Form\Field\ValidationResult;

final class CreditCard extends Field//implements Composite
{
	public static array $allowedAttributes = [
		// Attribute\Autocomplete::class,
		// Attribute\Region::class,
	];

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('credit-card');
	}

	public function getDefaultAttributes(): array
	{
		return [];
	}

	public function validate(mixed $value): ValidationResult
	{
		return ValidationResult::failed($value, 'Not implemented.');
	}
}
