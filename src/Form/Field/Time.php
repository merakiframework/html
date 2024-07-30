<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;
use Brick\DateTime\LocalTime;
use Brick\DateTime\DateTimeException;

final class Time extends Field
{
	public static array $allowedAttributes = [
		Attribute\Min::class,	// min time accepted in ISO 8601 format
		Attribute\Max::class,	// max time accepted in ISO 8601 format
		Attribute\Availability::class,	// @todo: implement this
	];

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('time');
	}

	public function getDefaultAttributes(): array
	{
		return [];
	}

	public function validate(mixed $value): ValidationResult
	{
		if (!is_string($value)) {
			return ValidationResult::failed('Value must be a string.');
		}

		$errors = [];
		$min = $this->attributes->find(Attribute\Min::class);
		$max = $this->attributes->find(Attribute\Max::class);

		try {
			$value = LocalTime::parse($value);
		} catch (DateTimeException $e) {
			return ValidationResult::failed($value, 'Time field is not valid: ' . $e->getMessage());
		}

		if ($min !== null) {
			$min = LocalTime::parse($min->value);

			if ($value->isBefore($min)) {
				$errors[] = 'Time must be ' . $min . ' or later.';
			}
		}

		if ($max !== null) {
			$max = LocalTime::parse($max->value);

			if ($value->isAfter($max)) {
				$errors[] = 'Time must be ' . $max . ' or earlier.';
			}
		}

		return ValidationResult::guess($value, $errors);
	}
}
