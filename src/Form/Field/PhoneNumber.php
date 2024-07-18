<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;

final class PhoneNumber extends Field
{
	/**
	 * The international phone number format as defined by E.123/E.164
	 * standard, consists of a country code, area code, and
	 * subscriber number.
	 *
	 * The format allows for the following characters:
	 * 	- an optional "+" prefix
	 * 	- digits 0-9
	 * 	- spaces
	 */
	public const INTERNATIONAL_PHONE_NUMBER_FORMAT = '/^\+?[0-9 ]+$/';

	// E.164 standard allows for a minimum of 3 digits.
	public const INTERNATIONAL_PHONE_NUMBER_MIN_LENGTH = 3;

	// E.164 standard allows for a maximum of 15 digits
	public const INTERNATIONAL_PHONE_NUMBER_MAX_LENGTH = 15;

	public static array $allowedOptions = [
		Attribute\Min::class,
		Attribute\Max::class,
		Attribute\Pattern::class,
	];

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('phone-number');
	}

	public function getDefaultAttributes(): array
	{
		return [
			new Attribute\Autocomplete('tel'),
		];
	}

	public function validate(mixed $value): ValidationResult
	{
		if (!is_string($value)) {
			return ValidationResult::failed('Value must be a string.');
		}

		$errors = [];
		$pattern = $this->attributes->find(Attribute\Pattern::class)
			?? new Attribute\Pattern(self::INTERNATIONAL_PHONE_NUMBER_FORMAT);
		$min = $this->attributes->find(Attribute\Min::class)
			?? new Attribute\Min(self::INTERNATIONAL_PHONE_NUMBER_MIN_LENGTH);
		$max = $this->attributes->find(Attribute\Max::class)
			?? new Attribute\Max(self::INTERNATIONAL_PHONE_NUMBER_MAX_LENGTH);

		if (!preg_match($pattern->value, $value)) {
			if ($pattern->value === self::INTERNATIONAL_PHONE_NUMBER_FORMAT) {
				$errors[] = 'Phone number can only contain digits, spaces, and a "+" prefix.';
			} else {
				$errors[] = 'Value must be a valid phone number.';
			}
		}

		// remember if the value had a "+" prefix
		// it is removed before length validation and added back after
		$hasPlusPrefix = strpos($value, '+') === 0;

		// remove all non-numeric characters
		// spaces are permanantly removed as they are only allowed for readability
		$value = preg_replace('/[^0-9]/', '', $value);

		if (mb_strlen($value) < $min->value) {
			$errors[] = 'Value must have at least ' . $min->value . ' digits.';
		}

		if (mb_strlen($value) > $max->value) {
			$errors[] = 'Value cannot have more than ' . $max->value . ' digits.';
		}

		// add back the "+" prefix if it was removed
		if ($hasPlusPrefix) {
			$value = "+$value";
		}

		return ValidationResult::guess($value, $errors);
	}
}
