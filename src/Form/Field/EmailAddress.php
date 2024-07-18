<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;

final class EmailAddress extends Field
{
	/**
	 * The default pattern allows:
	 * - any Unicode letter or number (as long as it's not a control character)
	 * - local part must contain at least one character
	 * - local part cannot contain "@" symbol
	 * - The domain and local parts are separated by the "@" symbol
	 * - domain part must contain at least one character
	 * - domain part cannot contain "@" symbol
	 */
	public const LOOKS_LIKE_EMAIL = '/^[^@\p{C}\p{Z}\p{Cc}\p{Cf}\p{Cn}\p{Zl}\p{Zp}]+@[^@\p{C}\p{Z}\p{Cc}\p{Cf}\p{Cn}\p{Zl}\p{Zp}]+$/u';

	/**
	 * Pattern to check if an email address is routable according to SMTP protocol.
	 */
	public const ROUTABLE_EMAIL = -1;

	public static array $allowedAttributes = [
		Attribute\Pattern::class,
		Attribute\Min::class,
		Attribute\Max::class,
	];

	public function getDefaultAttributes(): array
	{
		return [
			// new Attribute\Pattern(self::LOOKS_LIKE_EMAIL),
			// new Attribute\Min(3),
			// new Attribute\Max(255),
			new Attribute\Autocomplete('email'),
		];
	}

	public function validate(mixed $value): ValidationResult
	{
		if (!is_string($value)) {
			return ValidationResult::failed($value, 'Email address is not a string.');
		}

		$errors = [];
		$pattern = $this->attributes->findOrCreate(Attribute\Pattern::class, fn() => new Attribute\Pattern(self::LOOKS_LIKE_EMAIL));
		$min = $this->attributes->find(Attribute\Min::class);
		$max = $this->attributes->find(Attribute\Max::class);

		if ($pattern->value === self::ROUTABLE_EMAIL && !filter_var($value, \FILTER_VALIDATE_EMAIL, \FILTER_FLAG_EMAIL_UNICODE)) {
			$errors[] = 'Email address is not in the correct format.';
		} elseif (!preg_match($pattern->value, $value)) {
			$errors[] = 'Email address is not in the correct format.';
		}

		if ($min !== null && mb_strlen($value) < $min->value) {
			$errors[] = 'Email address must be at least ' . $min->value . ' characters long.';
		}

		if ($max !== null && mb_strlen($value) > $max->value) {
			$errors[] = 'Email address cannot be more than ' . $max->value . ' characters long.';
		}

		return ValidationResult::guess($value, $errors);
	}

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('email-address');
	}
}
