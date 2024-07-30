<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;

final class Text extends Field
{
	public static array $allowedAttributes = [
		Attribute\Min::class,		// min character length of the text field
		Attribute\Max::class,		// max character length of the text field
		Attribute\Pattern::class,	// regex pattern to match against
		Attribute\Multiline::class,	// allow linebreaks (usually renders as textarea instead of input)
		Attribute\Placeholder::class,
	];

	public function getDefaultAttributes(): array
	{
		return [];
	}

	public function validate(mixed $value): ValidationResult
	{
		if (!($value instanceof \Stringable) && !is_string($value)) {
			return ValidationResult::failed($value, 'Value must be a string.');
		}

		$errors = [];
		$min = $this->attributes->find(Attribute\Min::class);
		$max = $this->attributes->find(Attribute\Max::class);
		$pattern = $this->attributes->find(Attribute\Pattern::class);
		$multiline = $this->attributes->find(Attribute\Multiline::class);

		if ($min !== null && mb_strlen($value) < $min->value) {
			$errors[] = sprintf('Value must be at least %d characters long.', $min->value);
		}

		if ($max !== null && mb_strlen($value) > $max->value) {
			$errors[] = sprintf('Value must be at most %d characters long.', $max->value);
		}

		if ($pattern !== null && !preg_match($pattern->value, $value)) {
			$errors[] = 'Value does not match the required pattern.';
		}

		if ($multiline === null && preg_match('/\r|\n|\r\n/', $value) === 1) {
			$errors[] = 'Value must not contain linebreaks.';

		}

		return ValidationResult::guess($value, $errors);
	}

	protected function getType(): Attribute\Type
	{
		return new Attribute\Type('text');
	}
}
