<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;

/**
 * A "name" field is used to represent a person's full name.
 *
 * It does not make any assumptions about the structure of the name.
 * There are, however, some sane restrictions. A name can:
 *
 * 	- contain unicode letters, spaces, apostrophes, periods, and dashes
 * 	- consist of one or more "words" separated by spaces
 * 	- each "word" must be at least one character long
 *  - be at most 255 characters long
 *  - be at least 1 character long
 *  - Use Roman Numerals to represent numbers (e.g. John Doe IV)
 *
 * @see https://www.w3.org/International/questions/qa-personal-names
 * @see https://shinesolutions.com/2018/01/08/falsehoods-programmers-believe-about-names-with-examples/
 */
final class Name extends Field
{
	/**
	 * A full name can:
	 * 	- contain unicode letters, spaces, apostrophes, periods, and dashes
	 * 	- consist of one or more "words" separated by spaces
	 * 	- each "word" must be at least one character long
	 */
	public const DEFAULT_PATTERN = "/^[\p{L}\.'\-]+(?: [\p{L}\.'\-]+)*$/u";

	public static array $allowedAttributes = [
		Attribute\Pattern::class,
		Attribute\Min::class,		// measured in characters
		Attribute\Max::class,		// measured in characters
	];

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('name');
	}

	public function validate(mixed $value): ValidationResult
	{
		if (!is_string($value)) {
			return ValidationResult::failed($value, 'Value is not a valid name.');
		}

		$errors = [];
		$pattern = $this->attributes->get(Attribute\Pattern::class);
		$min = $this->attributes->find(Attribute\Min::class) ?? Attribute\Min::of(1);
		$max = $this->attributes->find(Attribute\Max::class) ?? Attribute\Max::of(255);

		if (!preg_match($pattern->value, $value)) {
			if ($pattern->value === self::DEFAULT_PATTERN) {
				$errors[] = 'Name can only contain letters, spaces, apostrophes, periods, and dashes.';
			} else {
				$errors[] = 'Name does not have the correct format.';
			}
		}

		if (mb_strlen($value) < $min->value) {
			$errors[] = "Name must have {$min->value} or more characters.";
		}

		if (mb_strlen($value) > $max->value) {
			$errors[] = "Name cannot have more than {$max->value} characters.";
		}

		return ValidationResult::guess($value, $errors);
	}

	public function getDefaultAttributes(): array
	{
		return [
			new Attribute\Pattern(self::DEFAULT_PATTERN),
			new Attribute\Min(1),
			new Attribute\Max(255),
			new Attribute\Autocomplete('name'),
		];
	}
}
